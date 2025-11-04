#!/bin/bash

# Script to view all recent logs with color coding
# Usage: ./view-all-logs.sh [lines]

LOG_FILE="storage/logs/laravel.log"
LINES="${1:-100}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
NC='\033[0m' # No Color

# Check if log file exists
if [ ! -f "$LOG_FILE" ]; then
    echo -e "${RED}Error: Log file not found: $LOG_FILE${NC}"
    exit 1
fi

echo -e "${BLUE}📋 Recent Logs (Last $LINES lines)${NC}"
echo -e "${BLUE}================================${NC}"
echo ""

# Show logs with color coding
tail -n "$LINES" "$LOG_FILE" | while IFS= read -r line; do
    if echo "$line" | grep -qiE "error|❌|failed|exception"; then
        echo -e "${RED}$line${NC}"
    elif echo "$line" | grep -qiE "success|✅|successful"; then
        echo -e "${GREEN}$line${NC}"
    elif echo "$line" | grep -qiE "warning|⚠️|warning"; then
        echo -e "${YELLOW}$line${NC}"
    elif echo "$line" | grep -qiE "shipping|🚀|dsp_order|shop_id"; then
        echo -e "${CYAN}$line${NC}"
    elif echo "$line" | grep -qiE "payment|noon|دفع"; then
        echo -e "${MAGENTA}$line${NC}"
    else
        echo "$line"
    fi
done

