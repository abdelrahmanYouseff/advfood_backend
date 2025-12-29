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
# API endpoint - use environment variable or default to local URL for development
# For production, set ZYDA_API_ENDPOINT environment variable
API_ENDPOINT = os.getenv("ZYDA_API_ENDPOINT", "https://advfoodapp.clarastars.com/api/zyda/orders")
LOOP_INTERVAL_SECONDS = 60
PROCESSED_PHONES_FILE = os.path.join(
    os.path.dirname(os.path.abspath(__file__)),
    "processed_zyda_phones.json",
)
SESSION_COOKIES_FILE = os.path.join(
    os.path.dirname(os.path.abspath(__file__)),
    "zyda_session_cookies.json",
)

INTERACTION_DELAY = 0.01  # Minimal delay for fastest execution

EMAIL = "abdelrahman.yousef@hadaf-hq.com"
PASSWORD = "V@ntom2121992"

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
    ".//p[contains(@class,'heading16_')][contains(text(),'SAR')]"
)
ORDER_ITEM_SELECTOR = ".flex.gap-2"
# Unique order identifier class pattern (the unique part changes for each order)
# e.g., element14_McQXd, element14_ABC123, etc.
ZYDA_ORDER_KEY_SELECTOR = "[class*='element14_']"  # Search for any element with element14_ in class

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


def load_session_cookies() -> Optional[List[Dict]]:
    """Load saved session cookies from file."""
    if not os.path.exists(SESSION_COOKIES_FILE):
        return None
    try:
        with open(SESSION_COOKIES_FILE, "r", encoding="utf-8") as fp:
            cookies = json.load(fp)
            if isinstance(cookies, list):
                print(f"[INFO] Loaded {len(cookies)} saved session cookie(s)", flush=True)
                return cookies
    except Exception as exc:
        print(f"[WARN] Failed to load session cookies: {exc}", flush=True)
    return None


def save_session_cookies(driver) -> None:
    """Save current session cookies to file."""
    try:
        cookies = driver.get_cookies()
        with open(SESSION_COOKIES_FILE, "w", encoding="utf-8") as fp:
            json.dump(cookies, fp, indent=2)
        print(f"[SUCCESS] Saved {len(cookies)} session cookie(s) for future use", flush=True)
    except Exception as exc:
        print(f"[WARN] Failed to save session cookies: {exc}", flush=True)


def is_session_valid(driver, wait: WebDriverWait) -> bool:
    """Check if current session is still valid by trying to access orders page."""
    try:
        print("[INFO] Checking if session is still valid...", flush=True)
        driver.get(ORDERS_URL)

        # Wait a bit for page to load
        time.sleep(0.5)  # Reduced from 2 to 0.5 seconds

        # Check if we're redirected to login page
        if "/sign-in" in driver.current_url:
            print("[INFO] Session expired (redirected to login page)", flush=True)
            return False

        # Try to find orders container (indicates we're logged in)
        try:
            wait.until(
                EC.presence_of_element_located((By.CSS_SELECTOR, ORDERS_CONTAINER_SELECTOR)),
                timeout=10
            )
            print("[SUCCESS] Session is valid", flush=True)
            return True
        except TimeoutException:
            # If we can't find orders container, session might be invalid
            print("[INFO] Session might be invalid (orders container not found)", flush=True)
            return False
    except Exception as exc:
        print(f"[WARN] Error checking session validity: {exc}", flush=True)
        return False


def scrape_orders() -> List[Dict[str, object]]:
    print("[STEP] Initializing browser...", flush=True)
    options = Options()
    options.add_argument("--headless=new")
    options.add_argument("--disable-gpu")
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--window-size=1920,1080")
    # Performance optimizations
    # Note: Can't disable JavaScript as Zyda site requires it
    # options.add_argument("--disable-images")  # May break the site
    options.add_argument("--disable-extensions")
    options.add_argument("--disable-plugins")
    options.add_argument("--disable-background-timer-throttling")
    options.add_argument("--disable-renderer-backgrounding")
    options.add_argument("--disable-backgrounding-occluded-windows")
    # Prefs to speed up
    prefs = {
        "profile.managed_default_content_settings.images": 2,  # Disable images
    }
    options.add_experimental_option("prefs", prefs)

    try:
        print("[INFO] Downloading/Updating ChromeDriver...", flush=True)
        try:
            driver = webdriver.Chrome(
                service=Service(ChromeDriverManager().install()),
                options=options,
            )
            print("[SUCCESS] Browser initialized successfully", flush=True)
        except Exception as chrome_exc:
            # Try alternative: use system ChromeDriver if available
            print(f"[WARN] ChromeDriverManager failed: {chrome_exc}", flush=True)
            print("[INFO] Trying to use system ChromeDriver...", flush=True)
            try:
                driver = webdriver.Chrome(options=options)
                print("[SUCCESS] Browser initialized using system ChromeDriver", flush=True)
            except Exception as system_exc:
                error_msg = f"Failed to initialize browser with both methods. ChromeDriverManager: {chrome_exc}, System: {system_exc}"
                print(f"[ERROR] {error_msg}", flush=True)
                raise RuntimeError(error_msg) from system_exc
    except Exception as exc:
        error_msg = f"Failed to initialize browser: {exc}"
        print(f"[ERROR] {error_msg}", flush=True)
        import traceback
        print(f"[ERROR] Traceback: {traceback.format_exc()}", flush=True)
        raise RuntimeError(error_msg) from exc

    wait = WebDriverWait(driver, 10)  # Reduced to 10 seconds for faster execution

    try:
        # Try to load saved session cookies first
        saved_cookies = load_session_cookies()
        session_used = False

        if saved_cookies:
            print("[STEP] Attempting to use saved session...", flush=True)
            try:
                # Navigate to the site first (required before adding cookies)
                driver.get(SITE_URL)
                time.sleep(0.3)  # Reduced from 1 to 0.3 seconds

                # Add saved cookies
                for cookie in saved_cookies:
                    try:
                        driver.add_cookie(cookie)
                    except Exception as e:
                        print(f"[WARN] Failed to add cookie: {e}", flush=True)

                print("[INFO] Loaded saved cookies, verifying session...", flush=True)

                # Check if session is still valid
                if is_session_valid(driver, wait):
                    print("[SUCCESS] Using saved session - no login required!", flush=True)
                    session_used = True
                else:
                    print("[INFO] Saved session expired, will login again...", flush=True)
            except Exception as exc:
                print(f"[WARN] Failed to use saved session: {exc}", flush=True)
                print("[INFO] Will login normally...", flush=True)

        # If no saved session or session invalid, perform login
        if not session_used:
            print("[STEP] Performing login...", flush=True)
            print("[STEP] Navigating to Zyda login page...", flush=True)
            driver.get(SITE_URL)
            print(f"[INFO] Current URL: {driver.current_url}", flush=True)

            print("[STEP] Waiting for login form to appear...", flush=True)
            email_input, password_input = _wait_for_inputs(wait)
            print("[SUCCESS] Login form found", flush=True)

            print("[STEP] Filling email address...", flush=True)
            _fill_credentials(email_input, password_input)
            print("[SUCCESS] Credentials filled", flush=True)

            print("[STEP] Clicking login button...", flush=True)
            _click_login(driver, wait)
            print("[INFO] Login button clicked, waiting for redirect...", flush=True)

            _wait_for_login_success(driver, wait)

            # Save session cookies after successful login
            print("[STEP] Saving session cookies for future use...", flush=True)
            save_session_cookies(driver)

        # Now scrape orders (session is ready)
        print("[STEP] Starting to scrape order cards from dashboard...", flush=True)
        orders = _scrape_order_cards(driver, wait)
        print(f"[SUCCESS] Successfully scraped {len(orders)} order(s)", flush=True)

        return orders
    except Exception as exc:
        error_msg = f"Error during scraping: {exc}"
        print(f"[ERROR] {error_msg}", flush=True)
        import traceback
        print(f"[ERROR] Traceback: {traceback.format_exc()}", flush=True)
        # Try to save screenshot for debugging
        try:
            if 'driver' in locals():
                driver.save_screenshot("scraping_error.png")
                print("[INFO] Saved screenshot to scraping_error.png for debugging", flush=True)
        except:
            pass
        raise
    finally:
        try:
            if 'driver' in locals():
                driver.quit()
                print("[INFO] Browser closed", flush=True)
        except:
            print("[WARN] Error closing browser (non-critical)", flush=True)


def _wait_for_inputs(wait: WebDriverWait):
    print("[INFO] Looking for email input field...", flush=True)
    email_input = wait.until(
        EC.visibility_of_element_located((By.CSS_SELECTOR, EMAIL_INPUT_SELECTOR))
    )
    print("[SUCCESS] Email input found", flush=True)

    print("[INFO] Looking for password input field...", flush=True)
    password_input = wait.until(
        EC.visibility_of_element_located((By.CSS_SELECTOR, PASSWORD_INPUT_SELECTOR))
    )
    print("[SUCCESS] Password input found", flush=True)
    return email_input, password_input


def _fill_credentials(email_input, password_input) -> None:
    print(f"[INFO] Entering email: {EMAIL[:5]}***", flush=True)
    email_input.clear()
    email_input.send_keys(EMAIL)
    print("[SUCCESS] Email entered", flush=True)

    print("[INFO] Entering password...", flush=True)
    password_input.clear()
    password_input.send_keys(PASSWORD)
    print("[SUCCESS] Password entered", flush=True)


def _click_login(driver, wait: WebDriverWait) -> None:
    print("[INFO] Looking for login button...", flush=True)
    button = wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, LOGIN_BUTTON_SELECTOR)))
    print("[SUCCESS] Login button found", flush=True)

    print("[INFO] Scrolling to login button...", flush=True)
    driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", button)
    time.sleep(INTERACTION_DELAY)

    print("[INFO] Attempting to click login button...", flush=True)
    try:
        button.click()
        print("[SUCCESS] Login button clicked (normal click)", flush=True)
    except (ElementClickInterceptedException, StaleElementReferenceException):
        print("[WARN] Normal click failed, trying JavaScript click...", flush=True)
        driver.execute_script("arguments[0].click();", button)
        print("[SUCCESS] Login button clicked (JavaScript click)", flush=True)


def _wait_for_login_success(driver, wait: WebDriverWait) -> None:
    try:
        print("[STEP] Waiting for login to complete...", flush=True)
        print(f"[INFO] Current URL before redirect: {driver.current_url}", flush=True)

        print("[INFO] Waiting for redirect away from sign-in page...", flush=True)
        wait.until(lambda d: "/sign-in" not in d.current_url)
        print(f"[INFO] Redirected! Current URL: {driver.current_url}", flush=True)

        if "/orders/current" not in driver.current_url:
            print("[INFO] Not on orders page yet, navigating to orders page...", flush=True)
            driver.get(ORDERS_URL)
            print(f"[INFO] Navigated to: {driver.current_url}", flush=True)
        else:
            print("[INFO] Already on orders page", flush=True)

        print("[INFO] Waiting for orders page to load completely...", flush=True)
        wait.until(EC.url_contains("/orders/current"))
        print("[SUCCESS] Orders page URL confirmed", flush=True)

        print("[INFO] Waiting for order cards container to appear...", flush=True)
        wait.until(
            EC.presence_of_element_located((By.CSS_SELECTOR, ORDERS_CONTAINER_SELECTOR))
        )
        print("[SUCCESS] Order cards container found", flush=True)
        print("[SUCCESS] Login successful and orders page ready!", flush=True)
    except TimeoutException as e:
        driver.save_screenshot("login_failure.png")
        current_url = driver.current_url
        error_msg = (
            f"Login did not complete within the expected time. "
            f"Current URL: {current_url}. "
            f"Saved screenshot to login_failure.png for troubleshooting."
        )
        print(f"[ERROR] Login failed: {error_msg}", flush=True)
        print(f"[ERROR] TimeoutException details: {str(e)}", flush=True)
        raise RuntimeError(error_msg) from None


def _scrape_order_cards(driver, wait: WebDriverWait) -> List[Dict[str, object]]:
    try:
        # Navigate to orders page first
        print("[STEP] Navigating to Zyda orders page...", flush=True)
        driver.get(ORDERS_URL)
        time.sleep(0.5)  # Reduced from 2 to 0.5 seconds

        print("[STEP] Fetching orders from Zyda dashboard...", flush=True)
        print("[INFO] Waiting for order cards to appear...", flush=True)
        cards = wait.until(
            EC.presence_of_all_elements_located(
                (By.CSS_SELECTOR, ORDERS_CONTAINER_SELECTOR)
            )
        )
        print(f"[SUCCESS] Found {len(cards)} order card(s)", flush=True)
    except TimeoutException:
        driver.save_screenshot("orders_not_found.png")
        error_msg = (
            "Could not find any order cards. "
            "Saved screenshot to orders_not_found.png for troubleshooting."
        )
        print(f"[ERROR] Failed to fetch orders: {error_msg}")
        raise RuntimeError(error_msg) from None

    scraped_orders: List[Dict[str, object]] = []
    total_cards = len(cards)

    # Track stats for summary
    order_stats = {
        "created": 0,
        "updated": 0,
        "skipped": 0,
        "failed": 0,
    }

    for idx in range(total_cards):
        try:
            # For first order, we're already on the orders page
            # For subsequent orders, go back to orders list
            if idx > 0:
                driver.get(ORDERS_URL)
                # Wait for orders to load after page reload (with shorter timeout)
                try:
                    short_wait = WebDriverWait(driver, 5)  # Reduced timeout
                    short_wait.until(
                        EC.presence_of_all_elements_located(
                            (By.CSS_SELECTOR, ORDERS_CONTAINER_SELECTOR)
                        )
                    )
                    time.sleep(0.2)  # Minimal wait for stability
                except TimeoutException:
                    time.sleep(0.5)  # Reduced fallback wait

            # Get cards again (refresh after reload or use existing)
            cards = driver.find_elements(By.CSS_SELECTOR, ORDERS_CONTAINER_SELECTOR)

            if idx >= len(cards):
                print(f"[WARN] Order #{idx + 1} not found (found {len(cards)} cards), skipping...")
                continue

            card = cards[idx]

            # Scroll to card to ensure it's visible
            driver.execute_script("arguments[0].scrollIntoView({block: 'center', behavior: 'auto'});", card)
            time.sleep(0.1)  # Reduced from 0.5 to 0.1 seconds

            card_label = _get_card_label(driver, wait, card)

            # Extract unique order key from card BEFORE opening it
            zyda_order_key = _get_zyda_order_key(driver, wait, card, idx)

            if not zyda_order_key or zyda_order_key.startswith("zyda_"):
                print(f"[WARN] Order #{idx + 1} missing valid order key (got: {zyda_order_key}), continuing anyway...")

            # Click on the card to open order details
            _open_order_card(driver, wait, card)

            # Extract order details from the opened order page
            details = _extract_order_details(driver, wait)

            phone = details.get("phone")
            if not phone:
                print(f"[WARN] Skipping order #{idx + 1} due to missing phone number.")
                # No need to reload here - we'll reload at the start of next iteration
                continue

            # Parse total amount from Zyda platform (exact amount as shown)
            raw_total = details.get("total")
            parsed_total = _parse_total_amount(raw_total)

            # Convert items to structured format (JSON) with name, quantity, and actual price from Zyda
            raw_items = details.get("items") or []
            structured_items = []
            for item_data in raw_items:
                # item_data is now a dict: {"quantity": "2x", "name": "Burger", "price": 37.0}
                if isinstance(item_data, dict):
                    quantity_str = item_data.get("quantity", "1x")
                    name = item_data.get("name", "")
                    price = item_data.get("price")  # Actual price from Zyda (or None)
                else:
                    # Fallback for old format (tuple)
                    quantity_str, name = item_data
                    price = None

                # Extract quantity number from string like "2x" -> 2
                quantity = int(re.sub(r'[^\d]', '', quantity_str)) if quantity_str else 1

                structured_items.append({
                    "name": name,
                    "quantity": quantity,
                    "price": price,  # Actual price from Zyda platform (preserve as is)
                })

            order_payload = {
                "name": card_label,
                "phone": phone,
                "address": details.get("address"),
                "total_amount": parsed_total,  # Use exact amount from Zyda platform
                "items": structured_items,  # Structured items with name, quantity, price
                "zyda_order_key": zyda_order_key,  # Unique order identifier from Zyda (e.g., "#GD7G-GAWP")
            }

            # Count unique items (by name) for items_count
            unique_items = set()
            for item in structured_items:
                item_name = item.get("name", "").strip().lower()
                if item_name:
                    unique_items.add(item_name)
            items_count = len(unique_items) if unique_items else len(structured_items)

            # Print order summary in green color
            _print_order_summary(idx + 1, {
                "name": card_label,
                "phone": phone,
                "address": details.get("address") or "N/A",
                "items_count": items_count,  # Count of unique items, not total quantities
                "order_key": zyda_order_key,
                "total": parsed_total,
            })

            # Send order to API immediately after extraction
            print(f"[STEP] Sending order #{idx + 1} to database immediately...", flush=True)
            operation = _send_order_to_api(order_payload, idx + 1, total_cards)

            # Track stats
            if operation in order_stats:
                order_stats[operation] += 1

            # Save processed phones periodically (every order to ensure no data loss)
            save_processed_phones()

            # Keep track for summary (optional, but useful)
            scraped_orders.append(order_payload)

            # No need to reload here - we'll reload at the start of next iteration if needed

        except Exception as exc:
            print(f"[ERROR] Error processing order #{idx + 1}: {exc}")
            # Try to go back to orders list
            try:
                driver.get(ORDERS_URL)
                time.sleep(0.3)  # Reduced from 1 to 0.3 seconds
            except:
                pass
            continue

    # Print summary of processed orders
    print(f"\n[INFO] Processing Summary:", flush=True)
    print(f"  - Created: {order_stats['created']}", flush=True)
    print(f"  - Updated: {order_stats['updated']}", flush=True)
    print(f"  - Skipped: {order_stats['skipped']}", flush=True)
    print(f"  - Failed: {order_stats['failed']}", flush=True)

    return scraped_orders


def _get_zyda_order_key(driver, wait: WebDriverWait, card, idx: int = 0) -> str:
    """
    Extract unique order key from card using element14_* class pattern.
    The value is the text content inside the <p> element with class element14_*.
    Example: <p class="element14_McQXd">#GD7G-GAWP</p> -> returns "#GD7G-GAWP"
    This is the unique identifier for each order from Zyda.

    IMPORTANT: We look for text that starts with '#' to identify the order key
    (not the date/time which also has element14_ class).

    Structure example:
    <div class="flex justify-between">
        <div class="flex flex-col">
            <p class="heading20_fe6KU">abdelrahman</p>
            <p class="element14_McQXd">#GD7G-GAWP</p>  <!-- This is what we need -->
        </div>
    </div>
    """
    # Method 1: Search for <p> element with class containing element14_ that starts with '#'
    try:
        # Look for <p> elements with element14_ in class within the card
        # Filter by text starting with '#' (order key) vs date/time
        key_elements = card.find_elements(By.XPATH, ".//p[contains(@class, 'element14_')]")
        for key_element in key_elements:
            # Get the text content (e.g., "#GD7G-GAWP")
            text_content = key_element.text.strip()
            # Only return if it starts with '#' (order key), not date/time
            if text_content and text_content.startswith('#'):
                return text_content
    except Exception as exc:
        print(f"[WARN] Error searching for order key in card (Method 1): {exc}")
        pass

    # Method 2: Search using CSS selector with element14_ pattern, filter by '#' prefix
    try:
        # Search for any element with class containing element14_
        key_elements = card.find_elements(By.CSS_SELECTOR, ZYDA_ORDER_KEY_SELECTOR)
        for key_element in key_elements:
            # Get the text content
            text_content = key_element.text.strip()
            # Only return if it starts with '#' (order key), not date/time
            if text_content and text_content.startswith('#'):
                print(f"[INFO] Found order key from card element (CSS): {text_content}")
                return text_content
    except Exception as exc:
        print(f"[WARN] Error searching for order key using CSS selector (Method 2): {exc}")
        pass

    # Method 3: Search in parent/ancestor div that contains the card structure
    try:
        # Look for div with "flex flex-col" that contains the p with element14_
        parent_divs = card.find_elements(By.XPATH, ".//div[contains(@class, 'flex') and contains(@class, 'flex-col')]")
        for parent_div in parent_divs:
            key_elements = parent_div.find_elements(By.XPATH, ".//p[contains(@class, 'element14_')]")
            for key_element in key_elements:
                text_content = key_element.text.strip()
                # Only return if it starts with '#' (order key), not date/time
                if text_content and text_content.startswith('#'):
                    print(f"[INFO] Found order key from parent div: {text_content}")
                    return text_content
    except Exception as exc:
        print(f"[WARN] Error searching for order key in parent div (Method 3): {exc}")
        pass

    # Method 4: Try to search in the entire page (in case the element is outside the card)
    try:
        key_elements = driver.find_elements(By.XPATH, "//p[contains(@class, 'element14_')]")
        for key_element in key_elements:
            text_content = key_element.text.strip()
            # Only return if it starts with '#' (order key), not date/time
            if text_content and text_content.startswith('#'):
                print(f"[INFO] Found order key from page: {text_content}")
                return text_content
    except Exception as exc:
        print(f"[WARN] Error searching for order key in page (Method 4): {exc}")
        pass

    # Fallback: try to get from card's id or data attributes
    try:
        card_id = card.get_attribute("id") or card.get_attribute("data-order-id") or card.get_attribute("data-id")
        if card_id:
            print(f"[INFO] Using card ID as order key: {card_id}")
            return card_id
    except Exception:
        pass

    # Last resort: generate a unique key based on timestamp and index
    order_timestamp = time.strftime("%Y%m%d%H%M%S")
    unique_key = f"zyda_{order_timestamp}_{idx}"
    print(f"[ERROR] No order key found (element14_* class), generated fallback: {unique_key}")
    return unique_key


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

    # Reduced wait time from default to 5 seconds for faster execution
    short_wait = WebDriverWait(driver, 5)
    short_wait.until(
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
    """
    Collect total amount from order details.
    IMPORTANT: Look for heading16_2tUu6 class which contains the exact total from Zyda.
    """
    # First try: Look for element with heading16_ class that contains SAR
    try:
        # Look for all elements with heading16_ class
        total_elements = driver.find_elements(By.XPATH, "//p[contains(@class, 'heading16_') and contains(text(), 'SAR')]")
        for element in total_elements:
            text = element.text.strip()
            # Check if it contains SAR and a number
            if 'SAR' in text and re.search(r'\d', text):
                # Extract the value (should be the total)
                print(f"[INFO] Found total using heading16_ selector: {text}")
                return text
    except Exception as exc:
        print(f"[WARN] Error searching for total using heading16_: {exc}")
        pass

    # Fallback: Original method
    containers = driver.find_elements(By.XPATH, TOTAL_CONTAINER_SELECTOR)
    for container in reversed(containers):
        rows = container.find_elements(By.XPATH, TOTAL_ROWS_SELECTOR)
        # Look for the row that contains "Total" (final total, not subtotal)
        for row in rows:
            label_el = row.find_elements(By.XPATH, TOTAL_LABEL_SELECTOR)
            value_el = row.find_elements(By.XPATH, TOTAL_VALUE_SELECTOR)
            if label_el and value_el:
                label = label_el[0].text.strip()
                value = value_el[-1].text.strip()
                # Only return if it's the final "Total" (not "Subtotal" or "Tax")
                if label and "subtotal" not in label.lower() and "total" in label.lower() and value:
                    return value
    return None


def _collect_total(driver) -> Optional[str]:
    """
    Get the FINAL TOTAL amount from the order page.
    IMPORTANT: The total is in an element with class heading16_2tUu6 specifically.
    This should match exactly what is shown on Zyda platform.
    """
    # Method 1: Look specifically for heading16_2tUu6 class (the exact total element)
    try:
        # First try exact class name heading16_2tUu6
        total_elements = driver.find_elements(By.XPATH, "//p[contains(@class, 'heading16_2tUu6') and contains(text(), 'SAR')]")
        if total_elements:
            text = total_elements[0].text.strip()
            if 'SAR' in text and re.search(r'\d', text):
                return text

        # If not found, try any heading16_ class that contains SAR
        # But prefer the last one (usually the total, not subtotal)
        total_elements = driver.find_elements(By.XPATH, "//p[contains(@class, 'heading16_') and contains(text(), 'SAR')]")
        if total_elements:
            # Get the last element (usually the total)
            element = total_elements[-1]
            text = element.text.strip()
            # Check if it contains SAR and a number (should be the total)
            if 'SAR' in text and re.search(r'\d', text):
                return text
    except Exception:
        pass

    # Method 2: Try original method
    total = _collect_totals(driver)
    if total:
        return total

    # Fallback: try to find Total label and its value
    try:
        total_el = driver.find_element(By.XPATH, "//p[text()[contains(.,'Total')] and not(contains(.,'Subtotal'))]")
        sibling = total_el.find_element(By.XPATH, "following::p[contains(text(),'SAR')][1]")
        total_text = sibling.text.strip()
        return total_text
    except Exception:
        return None


def _collect_items(driver) -> List[dict]:
    """
    Collect order items from the order page with their actual prices from Zyda.
    Returns list of dicts: [{"quantity": "2x", "name": "Burger", "price": 37.0}, ...]
    IMPORTANT: Extract exact prices as shown on Zyda platform.
    """
    rows = driver.find_elements(By.CSS_SELECTOR, ORDER_ITEM_SELECTOR)
    items: List[dict] = []
    seen: set[tuple[str, str]] = set()

    if not rows:
        return items

    for row_idx, row in enumerate(rows):
        raw_text = row.text.strip()
        if not raw_text:
            continue

        lines = [
            line.strip()
            for line in raw_text.splitlines()
            if line.strip()
        ]

        # Try to extract price from the row (look for SAR values)
        item_price = None
        for line in lines:
            # Look for price pattern: "37 SAR" or "37.00 SAR" or "SAR 37"
            price_match = re.search(r'(\d+(?:[.,]\d+)?)\s*SAR', line, re.IGNORECASE)
            if price_match:
                price_str = price_match.group(1).replace(',', '')
                try:
                    item_price = float(price_str)
                    break
                except ValueError:
                    pass

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
                        # Store as dict with quantity, name, and price (if found)
                        item_data = {
                            "quantity": quantity,
                            "name": name,
                            "price": item_price,  # Actual price from Zyda, or None if not found
                        }
                        items.append(item_data)
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
        # Create a shorter wait for faster execution (3 seconds instead of default)
        from selenium.webdriver.support.ui import WebDriverWait as WDW
        short_wait = WDW(wait._driver, timeout=3)
        element = short_wait.until(EC.visibility_of_element_located((by, locator)))
        return element.text.strip()
    except TimeoutException:
        return None


def _print_order_summary(order_num: int, order_data: dict) -> None:
    """
    Print a formatted order summary in green color.
    """
    GREEN = '\033[92m'  # ANSI color code for green
    RESET = '\033[0m'   # ANSI reset code

    print(f"\n{GREEN}{'='*60}", flush=True)
    print(f"ORDER #{order_num}", flush=True)
    print(f"{'='*60}", flush=True)
    print(f"Name:        {order_data['name']}", flush=True)
    print(f"Phone:       {order_data['phone']}", flush=True)
    print(f"Address:     {order_data['address']}", flush=True)
    print(f"Items Count: {order_data['items_count']}", flush=True)
    print(f"Order Key:   {order_data['order_key']}", flush=True)
    print(f"Total:       {order_data['total']} SAR", flush=True)
    print(f"{'='*60}{RESET}\n", flush=True)


def _parse_total_amount(value: Optional[str]) -> float:
    """
    Parse total amount string to float.
    IMPORTANT: Extract only the number, preserving decimal points.
    Example: "74.00 SAR" -> 74.0, "148 SAR" -> 148.0
    """
    if not value:
        return 0.0

    # Remove common prefixes/suffixes and extract number
    # Match: numbers with optional decimal point, may have commas as thousands separator
    matches = re.findall(r"[-+]?\d+(?:[.,]\d+)?", value)

    if not matches:
        return 0.0

    # Take the last match (usually the final total amount if there are multiple numbers)
    last_match = matches[-1]
    # Remove commas (thousands separator) and handle decimal point
    normalized = last_match.replace(",", "").replace(",", ".")

    try:
        amount = float(normalized)
        return amount
    except ValueError:
        return 0.0


def _send_order_to_api(order: Dict[str, object], order_num: int, total_orders: int) -> str:
    """
    Send a single order to the API immediately after extraction.
    Returns: operation type ("created", "updated", "skipped", "failed")
    """
    global processed_phones

    phone = order.get("phone")
    if not phone:
        print(f"[WARN] Skipping order #{order_num} - No phone number", flush=True)
        return "skipped"

    zyda_order_key = order.get("zyda_order_key")
    if not zyda_order_key:
        print(f"[WARN] Skipping order #{order_num} - No zyda_order_key: Phone={phone}", flush=True)
        return "skipped"

    was_processed = phone in processed_phones

    payload = {
        "name": order.get("name") or None,
        "phone": phone,
        "address": order.get("address") or None,
        "items": order.get("items", []) or [],
        "total_amount": order.get("total_amount", 0) or 0,
        "zyda_order_key": zyda_order_key,
    }

    print(f"[STEP] Sending order #{order_num}/{total_orders} to API: Key={zyda_order_key}, Phone={phone}", flush=True)

    try:
        print(f"[DEBUG] Sending POST request to: {API_ENDPOINT}", flush=True)
        response = requests.post(API_ENDPOINT, json=payload, timeout=30)
        print(f"[INFO] Response Status: {response.status_code}", flush=True)

        # Log response content for debugging
        try:
            response_text = response.text[:500]  # First 500 chars
            print(f"[DEBUG] Response text preview: {response_text}", flush=True)
        except:
            pass

        response.raise_for_status()
        data = response.json()
        operation = (data.get("operation") or data.get("message", "created")).lower()
        print(f"[DEBUG] Operation from API: {operation}", flush=True)

        if operation == "created" or "created" in operation or "نجاح" in str(data.get("message", "")):
            print(f"[SUCCESS] Order #{order_num} created: {zyda_order_key}", flush=True)
            if not was_processed:
                processed_phones.add(phone)
            return "created"
        elif operation == "skipped" or "exists" in operation or "موجود" in str(data.get("message", "")):
            print(f"[INFO] Order #{order_num} already exists (skipped): {zyda_order_key}", flush=True)
            return "skipped"
        elif operation == "updated" or "updated" in operation:
            print(f"[INFO] Order #{order_num} updated: {zyda_order_key}", flush=True)
            return "updated"
        else:
            print(f"[INFO] Order #{order_num} processed (assumed created): {zyda_order_key}", flush=True)
            if not was_processed:
                processed_phones.add(phone)
            return "created"
    except requests.exceptions.Timeout:
        error_msg = f"Timeout while syncing order {phone} (Key: {zyda_order_key})"
        print(f"[ERROR] {error_msg}", flush=True)
        return "failed"
    except requests.exceptions.ConnectionError as exc:
        error_msg = f"Connection error while syncing order {phone} (Key: {zyda_order_key}): {exc}"
        print(f"[ERROR] {error_msg}", flush=True)
        return "failed"
    except requests.exceptions.HTTPError as exc:
        error_msg = f"HTTP error while syncing order {phone} (Key: {zyda_order_key}): {exc}"
        if hasattr(exc, 'response') and exc.response is not None:
            try:
                error_text = exc.response.text[:500]
                error_msg += f" (HTTP {exc.response.status_code}: {error_text})"
                print(f"[ERROR] Response text: {error_text}", flush=True)
                print(f"[ERROR] Response headers: {dict(exc.response.headers)}", flush=True)
            except:
                pass
        print(f"[ERROR] {error_msg}", flush=True)
        return "failed"
    except requests.exceptions.RequestException as exc:
        error_msg = f"Failed to sync order {phone} (Key: {zyda_order_key}): {exc}"
        if hasattr(exc, 'response') and exc.response is not None:
            try:
                error_text = exc.response.text[:500]
                error_msg += f" (HTTP {exc.response.status_code}: {error_text})"
                print(f"[ERROR] Response text: {error_text}", flush=True)
            except:
                pass
        print(f"[ERROR] {error_msg}", flush=True)
        return "failed"
    except Exception as exc:
        import traceback
        print(f"[ERROR] Unexpected error syncing order {phone} (Key: {zyda_order_key}): {exc}", flush=True)
        print(f"[ERROR] Traceback: {traceback.format_exc()}", flush=True)
        return "failed"


def sync_orders(orders: List[Dict[str, object]]) -> Dict[str, int]:
    global processed_phones

    print(f"[STEP] Starting to sync {len(orders)} order(s) to Laravel API...", flush=True)
    print(f"[INFO] API Endpoint: {API_ENDPOINT}", flush=True)

    stats = {
        "total": len(orders),
        "created": 0,
        "updated": 0,
        "skipped": 0,
        "failed": 0,
    }

    changed = False

    for idx, order in enumerate(orders, 1):
        phone = order.get("phone")
        if not phone:
            stats["skipped"] += 1
            print(f"[WARN] Skipping order #{idx} - No phone number", flush=True)
            continue

        zyda_order_key = order.get("zyda_order_key")
        if not zyda_order_key:
            stats["skipped"] += 1
            print(f"[WARN] Skipping order #{idx} - No zyda_order_key: Phone={phone}", flush=True)
            continue

        # Check if phone was processed before, but still send to allow updates
        was_processed = phone in processed_phones

        payload = {
            "name": order.get("name") or None,
            "phone": phone,
            "address": order.get("address") or None,
            "items": order.get("items", []) or [],
            "total_amount": order.get("total_amount", 0) or 0,
            "zyda_order_key": zyda_order_key,
        }

        # Debug: Print payload (without sensitive data)
        print(f"[DEBUG] Payload for order #{idx}: Key={zyda_order_key}, Phone={phone}, Total={payload['total_amount']}, Items={len(payload['items'])}", flush=True)

        print(f"[STEP] Sending order #{idx}/{len(orders)}: Key={zyda_order_key}, Phone={phone}", flush=True)
        print(f"[DEBUG] API Endpoint: {API_ENDPOINT}", flush=True)
        print(f"[DEBUG] Payload keys: {list(payload.keys())}", flush=True)

        try:
            print(f"[INFO] Making POST request to {API_ENDPOINT}...", flush=True)
            response = requests.post(API_ENDPOINT, json=payload, timeout=30)  # Reduced from 60 to 30 seconds
            print(f"[INFO] Response received: Status {response.status_code}", flush=True)

            print(f"[INFO] Response Status: {response.status_code}", flush=True)

            response.raise_for_status()
            data = response.json()
            operation = (data.get("operation") or data.get("message", "created")).lower()

            print(f"[INFO] Operation: {operation}, Response: {data}", flush=True)

            if operation == "created" or "created" in operation or "نجاح" in str(data.get("message", "")):
                stats["created"] += 1
                print(f"[SUCCESS] Order #{idx} created: {zyda_order_key}", flush=True)
            elif operation == "skipped" or "exists" in operation or "موجود" in str(data.get("message", "")):
                stats["skipped"] += 1
                print(f"[INFO] Order #{idx} already exists (skipped): {zyda_order_key}", flush=True)
            elif operation == "updated" or "updated" in operation:
                stats["updated"] += 1
                print(f"[INFO] Order #{idx} updated: {zyda_order_key}", flush=True)
            else:
                # Default to created if operation is unclear but response is successful
                stats["created"] += 1
                print(f"[INFO] Order #{idx} processed (assumed created): {zyda_order_key}", flush=True)

            # Only mark as processed if it was a new order (created)
            # This allows updates for existing orders on subsequent runs
            if not was_processed:
                processed_phones.add(phone)
                changed = True
            elif operation == "updated" or "updated" in operation:
                # If order was updated, also save the processed phones to track changes
                changed = True
        except requests.exceptions.Timeout:
            stats["failed"] += 1
            error_msg = f"Timeout while syncing order {phone} (Key: {zyda_order_key})"
            print(f"[ERROR] {error_msg}", flush=True)
        except requests.exceptions.ConnectionError as exc:
            stats["failed"] += 1
            error_msg = f"Connection error while syncing order {phone} (Key: {zyda_order_key}): {exc}"
            print(f"[ERROR] {error_msg}", flush=True)
        except requests.exceptions.HTTPError as exc:
            stats["failed"] += 1
            error_msg = f"HTTP error while syncing order {phone} (Key: {zyda_order_key}): {exc}"
            if hasattr(exc, 'response') and exc.response is not None:
                try:
                    error_text = exc.response.text[:500]
                    error_msg += f" (HTTP {exc.response.status_code}: {error_text})"
                    print(f"[ERROR] Response text: {error_text}", flush=True)
                    print(f"[ERROR] Response headers: {dict(exc.response.headers)}", flush=True)
                except:
                    pass
            print(f"[ERROR] {error_msg}", flush=True)
        except requests.exceptions.RequestException as exc:
            stats["failed"] += 1
            error_msg = f"Failed to sync order {phone} (Key: {zyda_order_key}): {exc}"
            if hasattr(exc, 'response') and exc.response is not None:
                try:
                    error_text = exc.response.text[:500]
                    error_msg += f" (HTTP {exc.response.status_code}: {error_text})"
                    print(f"[ERROR] Response text: {error_text}", flush=True)
                except:
                    pass
            print(f"[ERROR] {error_msg}", flush=True)
        except Exception as exc:
            stats["failed"] += 1
            import traceback
            print(f"[ERROR] Unexpected error syncing order {phone} (Key: {zyda_order_key}): {exc}", flush=True)
            print(f"[ERROR] Traceback: {traceback.format_exc()}", flush=True)

    if changed:
        save_processed_phones()
        print(f"[INFO] Saved processed phones to file", flush=True)

    # Print summary
    print(f"\n[INFO] Sync Summary:", flush=True)
    print(f"  - Total orders: {stats['total']}", flush=True)
    print(f"  - Created: {stats['created']}", flush=True)
    print(f"  - Updated: {stats['updated']}", flush=True)
    print(f"  - Skipped: {stats['skipped']}", flush=True)
    print(f"  - Failed: {stats['failed']}", flush=True)

    print(
        "SUMMARY created={created} updated={updated} skipped={skipped} failed={failed}".format(
            created=stats["created"],
            updated=stats["updated"],
            skipped=stats["skipped"],
            failed=stats["failed"],
        ),
        flush=True
    )

    return stats


def main_loop() -> None:
    global processed_phones
    print("[INFO] Starting Zyda scraper (continuous loop mode)...")
    processed_phones = load_processed_phones()

    print(f"[INFO] Loaded {len(processed_phones)} processed phone(s).")

    cycle_count = 0
    while True:
        cycle_count += 1
        start_time = time.time()
        print(f"\n{'='*60}")
        print(f"[CYCLE] Starting cycle #{cycle_count}")
        print(f"{'='*60}")
        try:
            orders = scrape_orders()
            if orders:
                sync_orders(orders)
            else:
                print("[WARN] No orders found in this cycle.")
                print("SUMMARY created=0 updated=0 skipped=0 failed=0")
        except KeyboardInterrupt:
            print("[INFO] Scraper stopped by user.")
            break
        except Exception as exc:
            error_msg = f"Scraper cycle failed: {exc}"
            print(f"[ERROR] {error_msg}")
            import traceback
            print(f"[ERROR] Traceback: {traceback.format_exc()}")
            print("SUMMARY created=0 updated=0 skipped=0 failed=1")

        elapsed = time.time() - start_time
        sleep_for = max(LOOP_INTERVAL_SECONDS - elapsed, 10)
        print(f"[INFO] Cycle #{cycle_count} completed in {int(elapsed)} second(s).")
        print(f"[INFO] Sleeping for {int(sleep_for)} second(s) before next cycle.")
        time.sleep(sleep_for)


def run_once() -> Dict[str, int]:
    global processed_phones
    print("[INFO] Starting Zyda scraper (single run)...", flush=True)
    print(f"[INFO] API Endpoint: {API_ENDPOINT}", flush=True)
    print(f"[INFO] Python version: {sys.version}", flush=True)

    # Check if required modules are available
    try:
        import selenium
        print(f"[INFO] Selenium version: {selenium.__version__}", flush=True)
    except ImportError:
        print("[ERROR] Selenium module not found. Please install: pip install selenium", flush=True)
        print("SUMMARY created=0 updated=0 skipped=0 failed=1", flush=True)
        return {"total": 0, "created": 0, "updated": 0, "skipped": 0, "failed": 1}

    try:
        import requests
        print(f"[INFO] Requests version: {requests.__version__}", flush=True)
    except ImportError:
        print("[ERROR] Requests module not found. Please install: pip install requests", flush=True)
        print("SUMMARY created=0 updated=0 skipped=0 failed=1", flush=True)
        return {"total": 0, "created": 0, "updated": 0, "skipped": 0, "failed": 1}

    processed_phones = load_processed_phones()
    print(f"[INFO] Loaded {len(processed_phones)} processed phone(s).", flush=True)

    try:
        print("[STEP] Starting to scrape orders from Zyda dashboard...", flush=True)
        orders = scrape_orders()
        print(f"[INFO] Scraped {len(orders) if orders else 0} order(s) from Zyda", flush=True)

        if orders:
            print("[STEP] Starting to sync orders to Laravel API...", flush=True)
            return sync_orders(orders)
        else:
            print("[WARN] No orders found in this run.", flush=True)
            print("SUMMARY created=0 updated=0 skipped=0 failed=0", flush=True)
            return {"total": 0, "created": 0, "updated": 0, "skipped": 0, "failed": 0}
    except KeyboardInterrupt:
        print("[INFO] Scraper interrupted by user.", flush=True)
        raise
    except Exception as exc:
        error_msg = f"Scraper run failed: {exc}"
        print(f"[ERROR] {error_msg}", flush=True)
        import traceback
        print(f"[ERROR] Traceback: {traceback.format_exc()}", flush=True)
        print("SUMMARY created=0 updated=0 skipped=0 failed=1", flush=True)
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

