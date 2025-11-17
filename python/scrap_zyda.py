import argparse
import json
import os
import re
import sys
import time
from typing import Dict, List, Optional

import requests
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait
from selenium.common.exceptions import (
    ElementClickInterceptedException,
    StaleElementReferenceException,
    TimeoutException,
)
from webdriver_manager.chrome import ChromeDriverManager

SITE_URL = "https://dash.zyda.com/sign-in"
ORDERS_URL = (
    "https://dash.zyda.com/5617/orders/current"
    "?branch=all&date=all&driverId=&isManualOrder=false"
    "&searchStatus=&searchValue=&sortBy=created_at"
)
API_ENDPOINT = "http://127.0.0.1:8000/api/zyda/orders"
LOOP_INTERVAL_SECONDS = 60
PROCESSED_PHONES_FILE = os.path.join(
    os.path.dirname(os.path.abspath(__file__)),
    "processed_zyda_phones.json",
)

INTERACTION_DELAY = 0.2

EMAIL = "abdelrahman.yousef@hadaf-hq.com"
PASSWORD = "ArIUXhwb20qCmY"

LOGIN_BUTTON_SELECTOR = "[data-testid='login-button'], button.login-button"
EMAIL_INPUT_SELECTOR = (
    "input[type='email'], input[name='email'], input[data-testid='email-input']"
)
PASSWORD_INPUT_SELECTOR = (
    "input[type='password'], input[name='password'], input[data-testid='password-input']"
)
ORDERS_CONTAINER_SELECTOR = ".mb-4.rounded-md.flex.flex-col.gap-3"
DASHBOARD_SELECTOR = ".ant-layout, [data-testid='dashboard-root']"
PHONE_SELECTOR = "//div[@role='presentation']//p[contains(@class,'body16')]"
ADDRESS_SELECTOR = (
    "//span[contains(@style,'direction: ltr')]//p[contains(@class,'body16')]"
)
TOTAL_CONTAINER_SELECTOR = (
    "//div[contains(@class,'w-full') and contains(@style,'direction: ltr')]"
)
TOTAL_ROWS_SELECTOR = (
    ".//div[contains(@class,'flex') and contains(@class,'justify-between')]"
)
TOTAL_LABEL_SELECTOR = ".//p[contains(@class,'heading16_') or contains(text(),'Total')]"
TOTAL_VALUE_SELECTOR = (
    ".//p[contains(@class,'heading16_') or contains(@class,'body16_')][contains(text(),'SAR')]"
)
ORDER_ITEM_SELECTOR = ".flex.gap-2"

processed_phones: set[str] = set()


def load_processed_phones() -> set[str]:
    if not os.path.exists(PROCESSED_PHONES_FILE):
        return set()
    try:
        with open(PROCESSED_PHONES_FILE, "r", encoding="utf-8") as fp:
            payload = json.load(fp)
            if isinstance(payload, list):
                return set(payload)
    except Exception as exc:  # pragma: no cover - logging only
        print(f"[WARN] Failed to load processed phones: {exc}")
    return set()


def save_processed_phones() -> None:
    try:
        with open(PROCESSED_PHONES_FILE, "w", encoding="utf-8") as fp:
            json.dump(sorted(processed_phones), fp, ensure_ascii=False, indent=2)
    except Exception as exc:  # pragma: no cover - logging only
        print(f"[WARN] Failed to save processed phones: {exc}")


def scrape_orders() -> List[Dict[str, object]]:
    options = Options()
    options.add_argument("--headless=new")
    options.add_argument("--disable-gpu")
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--window-size=1920,1080")

    driver = webdriver.Chrome(
        service=Service(ChromeDriverManager().install()),
        options=options,
    )

    try:
        driver.get(SITE_URL)
        wait = WebDriverWait(driver, 40)

        email_input, password_input = _wait_for_inputs(wait)
        _fill_credentials(email_input, password_input)

        _click_login(driver, wait)
        _wait_for_login_success(driver, wait)
        orders = _scrape_order_cards(driver, wait)

        return orders
    finally:
        driver.quit()


def _wait_for_inputs(wait: WebDriverWait):
    email_input = wait.until(
        EC.visibility_of_element_located((By.CSS_SELECTOR, EMAIL_INPUT_SELECTOR))
    )
    password_input = wait.until(
        EC.visibility_of_element_located((By.CSS_SELECTOR, PASSWORD_INPUT_SELECTOR))
    )
    return email_input, password_input


def _fill_credentials(email_input, password_input) -> None:
    email_input.clear()
    email_input.send_keys(EMAIL)

    password_input.clear()
    password_input.send_keys(PASSWORD)


def _click_login(driver, wait: WebDriverWait) -> None:
    button = wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, LOGIN_BUTTON_SELECTOR)))
    driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", button)
    time.sleep(INTERACTION_DELAY)
    try:
        button.click()
    except (ElementClickInterceptedException, StaleElementReferenceException):
        driver.execute_script("arguments[0].click();", button)


def _wait_for_login_success(driver, wait: WebDriverWait) -> None:
    try:
        wait.until(lambda d: "/sign-in" not in d.current_url)
        if "/orders/current" not in driver.current_url:
            driver.get(ORDERS_URL)
        wait.until(EC.url_contains("/orders/current"))
        wait.until(
            EC.presence_of_element_located((By.CSS_SELECTOR, ORDERS_CONTAINER_SELECTOR))
        )
    except TimeoutException:
        driver.save_screenshot("login_failure.png")
        raise RuntimeError(
            "Login did not complete within the expected time. "
            "Saved screenshot to login_failure.png for troubleshooting."
        ) from None


def _scrape_order_cards(driver, wait: WebDriverWait) -> List[Dict[str, object]]:
    try:
        cards = wait.until(
            EC.presence_of_all_elements_located(
                (By.CSS_SELECTOR, ORDERS_CONTAINER_SELECTOR)
            )
        )
    except TimeoutException:
        driver.save_screenshot("orders_not_found.png")
        raise RuntimeError(
            "Could not find any order cards. "
            "Saved screenshot to orders_not_found.png for troubleshooting."
        ) from None

    scraped_orders: List[Dict[str, object]] = []

    for idx in range(len(cards)):
        cards = driver.find_elements(By.CSS_SELECTOR, ORDERS_CONTAINER_SELECTOR)
        if idx >= len(cards):
            break

        card = cards[idx]
        card_label = _get_card_label(driver, wait, card)

        _open_order_card(driver, wait, card)
        details = _extract_order_details(driver, wait)

        phone = details.get("phone")
        if not phone:
            continue

        items = [
            f"{quantity} {name}".strip()
            for quantity, name in (details.get("items") or [])
        ]

        order_payload = {
            "name": card_label,
            "phone": phone,
            "address": details.get("address"),
            "total_amount": _parse_total_amount(details.get("total")),
            "items": items or [],
        }

        scraped_orders.append(order_payload)

    return scraped_orders


def _get_card_label(driver, wait: WebDriverWait, card) -> str:
    try:
        header = card.find_element(
            By.XPATH, ".//p[contains(@class,'heading16_') or contains(@class,'heading14_')]"
        )
        text = header.text.strip()
        if text:
            return text
    except Exception:
        pass

    fallback = card.text.strip()
    if fallback:
        first_line = fallback.splitlines()[0].strip()
        if first_line:
            return first_line

    parent = card.find_element(By.XPATH, "./..")
    tag_elements = parent.find_elements(By.XPATH, ".//p[contains(@class,'heading')]")
    for el in tag_elements:
        label_text = el.text.strip()
        if label_text:
            return label_text

    return "[Card label not found]"


def _open_order_card(driver, wait: WebDriverWait, card) -> None:
    driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", card)
    time.sleep(INTERACTION_DELAY)
    try:
        card.click()
    except (ElementClickInterceptedException, StaleElementReferenceException):
        driver.execute_script("arguments[0].click();", card)

    wait.until(
        EC.any_of(
            EC.visibility_of_element_located((By.XPATH, PHONE_SELECTOR)),
            EC.visibility_of_element_located((By.XPATH, ADDRESS_SELECTOR)),
            EC.presence_of_element_located((By.XPATH, TOTAL_CONTAINER_SELECTOR)),
        )
    )
    time.sleep(INTERACTION_DELAY)


def _extract_order_details(driver, wait: WebDriverWait) -> dict:
    phone = _safe_text(wait, By.XPATH, PHONE_SELECTOR)
    address = _safe_text(wait, By.XPATH, ADDRESS_SELECTOR)
    total = _collect_total(driver)
    items = _collect_items(driver)

    return {
        "phone": phone,
        "address": address,
        "total": total,
        "items": items,
    }


def _collect_totals(driver) -> Optional[str]:
    containers = driver.find_elements(By.XPATH, TOTAL_CONTAINER_SELECTOR)
    for container in reversed(containers):
        rows = container.find_elements(By.XPATH, TOTAL_ROWS_SELECTOR)
        for row in rows:
            label_el = row.find_elements(By.XPATH, TOTAL_LABEL_SELECTOR)
            value_el = row.find_elements(By.XPATH, TOTAL_VALUE_SELECTOR)
            if label_el and value_el:
                label = label_el[0].text.strip()
                value = value_el[-1].text.strip()
                if label and "total" in label.lower() and value:
                    return value
    return None


def _collect_total(driver) -> Optional[str]:
    total = _collect_totals(driver)
    if total:
        return total

    try:
        total_el = driver.find_element(By.XPATH, "//p[text()[contains(.,'Total')]]")
        sibling = total_el.find_element(By.XPATH, "following::p[contains(text(),'SAR')][1]")
        return sibling.text.strip()
    except Exception:
        return None


def _collect_items(driver) -> List[tuple[str, str]]:
    rows = driver.find_elements(By.CSS_SELECTOR, ORDER_ITEM_SELECTOR)
    items: List[tuple[str, str]] = []
    seen: set[tuple[str, str]] = set()

    for row in rows:
        raw_text = row.text.strip()
        if not raw_text:
            continue

        lines = [
            line.strip()
            for line in raw_text.splitlines()
            if line.strip()
        ]

        i = 0
        while i < len(lines):
            line = lines[i]
            normalized = line.lower()

            if _is_quantity_line(normalized):
                quantity = line.replace(" ", "")
                name = _next_item_name(lines, i + 1)
                if name:
                    key = (quantity, name)
                    if key not in seen:
                        seen.add(key)
                        items.append(key)
                i += 1
            else:
                i += 1

    return items


def _is_quantity_line(line: str) -> bool:
    if not line:
        return False
    normalized = line.strip().lower().replace(" ", "")
    if normalized.endswith("items") or normalized.endswith("item"):
        return False
    return normalized.endswith("x") and normalized[:-1].isdigit()


def _next_item_name(lines: List[str], start_index: int) -> Optional[str]:
    skip_terms = {
        "subtotal",
        "total",
        "payment methods",
        "print",
        "english",
        "عربي",
        "thermal",
        "a4",
        "print receipt",
        "cancel order",
        "sort by",
        "creation time",
        "export",
        "accepted",
        "assigned to",
        "order",
    }
    for idx in range(start_index, len(lines)):
        candidate = lines[idx].strip()
        normalized = candidate.lower()
        if not candidate:
            continue
        if ":" in candidate:
            continue
        if normalized.startswith("sar"):
            continue
        if any(term in normalized for term in skip_terms):
            continue
        if normalized.endswith("mins") or normalized.endswith("min"):
            continue
        if normalized.startswith("#"):
            continue
        if _is_quantity_line(normalized):
            continue
        return candidate
    return None


def _safe_text(wait: WebDriverWait, by: By, locator: str) -> Optional[str]:
    try:
        element = wait.until(EC.visibility_of_element_located((by, locator)))
        return element.text.strip()
    except TimeoutException:
        return None


def _parse_total_amount(value: Optional[str]) -> float:
    if not value:
        return 0.0
    matches = re.findall(r"[0-9]+(?:[.,][0-9]+)?", value)
    if not matches:
        return 0.0
    normalized = matches[-1].replace(",", "")
    try:
        return float(normalized)
    except ValueError:
        return 0.0


def sync_orders(orders: List[Dict[str, object]]) -> Dict[str, int]:
    global processed_phones

    stats = {
        "total": len(orders),
        "created": 0,
        "updated": 0,
        "skipped": 0,
        "failed": 0,
    }

    changed = False

    for order in orders:
        phone = order.get("phone")
        if not phone:
            stats["skipped"] += 1
            continue

        if phone in processed_phones:
            stats["skipped"] += 1
            continue

        payload = {
            "name": order.get("name"),
            "phone": phone,
            "address": order.get("address"),
            "items": order.get("items", []),
            "total_amount": order.get("total_amount", 0),
        }

        try:
            response = requests.post(API_ENDPOINT, json=payload, timeout=60)
            response.raise_for_status()
            data = response.json()
            operation = (data.get("operation") or "created").lower()

            if operation == "created":
                stats["created"] += 1
            else:
                stats["updated"] += 1

            processed_phones.add(phone)
            changed = True
        except Exception as exc:
            stats["failed"] += 1
            print(f"[ERROR] Failed to sync order {phone}: {exc}")

    if changed:
        save_processed_phones()

    print(
        "SUMMARY created={created} updated={updated} skipped={skipped} failed={failed}".format(
            created=stats["created"],
            updated=stats["updated"],
            skipped=stats["skipped"],
            failed=stats["failed"],
        )
    )

    return stats


def main_loop() -> None:
    global processed_phones
    processed_phones = load_processed_phones()

    print(f"[INFO] Loaded {len(processed_phones)} processed phone(s).")

    while True:
        start_time = time.time()
        try:
            orders = scrape_orders()
            if orders:
                print(f"[INFO] Scraped {len(orders)} order(s).")
                sync_orders(orders)
            else:
                print("[INFO] No orders found in this cycle.")
                print("SUMMARY created=0 updated=0 skipped=0 failed=0")
        except Exception as exc:
            print(f"[ERROR] Scraper cycle failed: {exc}")
            print("SUMMARY created=0 updated=0 skipped=0 failed=1")

        elapsed = time.time() - start_time
        sleep_for = max(LOOP_INTERVAL_SECONDS - elapsed, 10)
        print(f"[INFO] Sleeping for {int(sleep_for)} second(s) before next cycle.")
        time.sleep(sleep_for)


def run_once() -> Dict[str, int]:
    global processed_phones
    processed_phones = load_processed_phones()
    print(f"[INFO] Loaded {len(processed_phones)} processed phone(s).")

    try:
        orders = scrape_orders()
        if orders:
            print(f"[INFO] Scraped {len(orders)} order(s).")
            return sync_orders(orders)
        else:
            print("[INFO] No orders found in this run.")
            print("SUMMARY created=0 updated=0 skipped=0 failed=0")
            return {"total": 0, "created": 0, "updated": 0, "skipped": 0, "failed": 0}
    except Exception as exc:
        print(f"[ERROR] Scraper run failed: {exc}")
        print("SUMMARY created=0 updated=0 skipped=0 failed=1")
        return {"total": 0, "created": 0, "updated": 0, "skipped": 0, "failed": 1}


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Sync Zyda orders into Laravel")
    parser.add_argument("--loop", action="store_true", help="Run continuously every minute")
    args = parser.parse_args()

    try:
        if args.loop:
            main_loop()
        else:
            run_once()
    except KeyboardInterrupt:
        print("[INFO] Zyda scraper stopped by user.")
        sys.exit(0)

