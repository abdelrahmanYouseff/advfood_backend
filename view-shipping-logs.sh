#!/bin/bash

# Script to view shipping-related logs on the server
# Usage: ./view-shipping-logs.sh [options]

LOG_FILE="storage/logs/laravel.log"
SHIPPING_KEYWORDS="shipping|Shipping|SHIPPING|dsp_order|shop_id|🚀|✅|❌|⚠️|🔍|🔴"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to show help
show_help() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  -f, --follow          Follow log file (like tail -f)"
    echo "  -l, --lines N         Show last N lines (default: 50)"
    echo "  -a, --all             Show all shipping logs (no limit)"
    echo "  -e, --error           Show only errors"
    echo "  -s, --success         Show only success messages"
    echo "  -o, --order-id ID     Filter by order ID"
    echo "  -d, --date DATE       Filter by date (YYYY-MM-DD)"
    echo "  -h, --help            Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 -f                 # Follow logs in real-time"
    echo "  $0 -l 100             # Show last 100 lines"
    echo "  $0 -e                 # Show only errors"
    echo "  $0 -o 123             # Show logs for order ID 123"
    echo "  $0 -d 2025-11-04      # Show logs for specific date"
}

# Default options
FOLLOW=false
LINES=50
SHOW_ALL=false
ERROR_ONLY=false
SUCCESS_ONLY=false
ORDER_ID=""
DATE_FILTER=""

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -f|--follow)
            FOLLOW=true
            shift
            ;;
        -l|--lines)
            LINES="$2"
            shift 2
            ;;
        -a|--all)
            SHOW_ALL=true
            shift
            ;;
        -e|--error)
            ERROR_ONLY=true
            shift
            ;;
        -s|--success)
            SUCCESS_ONLY=true
            shift
            ;;
        -o|--order-id)
            ORDER_ID="$2"
            shift 2
            ;;
        -d|--date)
            DATE_FILTER="$2"
            shift 2
            ;;
        -h|--help)
            show_help
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            show_help
            exit 1
            ;;
    esac
done

# Check if log file exists
if [ ! -f "$LOG_FILE" ]; then
    echo -e "${RED}Error: Log file not found: $LOG_FILE${NC}"
    exit 1
fi

# Build grep command
GREP_CMD="grep -iE '$SHIPPING_KEYWORDS' $LOG_FILE"

# Add error filter
if [ "$ERROR_ONLY" = true ]; then
    GREP_CMD="$GREP_CMD | grep -iE 'error|❌|⚠️|🔴|failed|Failed|FAILED'"
fi

# Add success filter
if [ "$SUCCESS_ONLY" = true ]; then
    GREP_CMD="$GREP_CMD | grep -iE 'success|✅|successful|Successfully'"
fi

# Add order ID filter
if [ ! -z "$ORDER_ID" ]; then
    GREP_CMD="$GREP_CMD | grep -iE 'order_id.*$ORDER_ID|order.*$ORDER_ID'"
fi

# Add date filter
if [ ! -z "$DATE_FILTER" ]; then
    GREP_CMD="$GREP_CMD | grep '$DATE_FILTER'"
fi

# Execute command
echo -e "${BLUE}📋 Shipping Logs Viewer${NC}"
echo -e "${BLUE}========================${NC}"
echo ""

if [ "$FOLLOW" = true ]; then
    echo -e "${YELLOW}Following logs (Ctrl+C to stop)...${NC}"
    echo ""
    eval "$GREP_CMD --line-buffered | tail -f"
elif [ "$SHOW_ALL" = true ]; then
    echo -e "${YELLOW}Showing all shipping logs...${NC}"
    echo ""
    eval "$GREP_CMD"
else
    echo -e "${YELLOW}Showing last $LINES matching lines...${NC}"
    echo ""
    eval "$GREP_CMD | tail -n $LINES"
fi

