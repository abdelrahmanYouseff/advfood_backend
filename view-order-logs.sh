#!/bin/bash

# Script to view order-related logs on the server
# Usage: ./view-order-logs.sh [order_id]

LOG_FILE="storage/logs/laravel.log"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

ORDER_ID="$1"

if [ -z "$ORDER_ID" ]; then
    echo -e "${BLUE}📋 Order Logs Viewer${NC}"
    echo -e "${BLUE}====================${NC}"
    echo ""
    echo "Usage: $0 <order_id>"
    echo ""
    echo "Examples:"
    echo "  $0 123              # Show logs for order ID 123"
    echo "  $0 123 -f           # Follow logs for order ID 123"
    echo ""
    echo "Recent orders:"
    echo ""
    # Show recent order IDs from logs
    grep -iE "order_id.*[0-9]+" "$LOG_FILE" | grep -oE "order_id['\"]?\s*[:=]\s*[0-9]+" | tail -20 | sort -u
    exit 1
fi

FOLLOW=false
if [ "$2" = "-f" ] || [ "$2" = "--follow" ]; then
    FOLLOW=true
fi

# Check if log file exists
if [ ! -f "$LOG_FILE" ]; then
    echo -e "${RED}Error: Log file not found: $LOG_FILE${NC}"
    exit 1
fi

echo -e "${BLUE}📋 Order Logs for Order ID: ${CYAN}$ORDER_ID${NC}"
echo -e "${BLUE}============================${NC}"
echo ""

if [ "$FOLLOW" = true ]; then
    echo -e "${YELLOW}Following logs (Ctrl+C to stop)...${NC}"
    echo ""
    grep --line-buffered -iE "order_id.*$ORDER_ID|order.*$ORDER_ID" "$LOG_FILE" | tail -f
else
    echo -e "${YELLOW}Showing logs for order ID: $ORDER_ID${NC}"
    echo ""
    grep -iE "order_id.*$ORDER_ID|order.*$ORDER_ID" "$LOG_FILE" | tail -50
fi

