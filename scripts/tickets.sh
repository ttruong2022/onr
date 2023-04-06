#!/bin/bash
​
# number of paramters paramters
# 0 = guess latest and previous tags, show between earliest and latest
# 1 = show between HEAD and $1
# 2 = show between earliest and latest
# 3+ = show between previous and latest excluding between earliest and previous
​
LATEST=$(printf '%s\n' "$@" | sort -Vru | head -1)
PREVIOUS=$(printf '%s\n' "$@" | sort -Vru | head -2 | tail -1)
EARLIEST=$(printf '%s\n' "$@" | sort -Vru | tail -1)
​
if [ -z "$LATEST" ]; then
    BRANCH=$(git branch --show-current)
    TAGS=$(git tag -l --merged $BRANCH)
​
    #RE='^\([^0-9]*\)\([0-9]*\)[.]\([0-9]*\)[.]\([0-9]*\)\([0-9A-Za-z-]*\)'
    RE='^\([^0-9]*\)\([0-9]*\)[.]\([0-9]*\)[.]\(.*\)'
    LATEST=$(echo "$TAGS" | sort -Vru | sed -e "s#$RE#\1\2.\3.\4#" | head -1)
    echo "Guessing Latest   = $LATEST"
​
    #PREVMINOR=$(git tag | sed -e "s#$REPREV#\1\2.\3#" | sort -V -r -u -t '.' | head -2 | tail -1 )
    PREVMINOR=$(echo "$TAGS" | sort -Vru | sed -e "s#$RE#\1\2.\3#" | sort -V -r -u | head -2 | tail -1)
    PREVIOUS=$(echo "$TAGS" | grep "$PREVMINOR" | sort -Vru | head -1)
    echo "Guessing Previous = $PREVIOUS"
    echo 
​
    EARLIEST=$PREVIOUS
fi;
​
if [ -z "$LATEST" ]; then
    echo "Must specify branches or tags"
    exit 1;
fi;
​
echo "Using Latest   = $LATEST"
echo "Using Previous = $PREVIOUS"
echo "Using Earliest = $EARLIEST"
echo
​
if [ "$LATEST" == "$PREVIOUS" ]; then
    echo "Tickets between $LATEST ... HEAD^"
    echo
    git log $LATEST..HEAD^ | grep -Eo '([A-Z]{3,}-)([0-9]+)' | sort -u
elif [ "$PREVIOUS" == "$EARLIEST" ]; then
    echo "Tickets between $PREVIOUS ... $LATEST"
    echo
    git log $PREVIOUS..$LATEST | grep -Eo '([A-Z]{3,}-)([0-9]+)' | sort -u
else
    echo "Tickets between $PREVIOUS ... $LATEST ignoring tickets between $EARLIEST ... $PREVIOUS"
    echo
    comm -23i \
        <(git log $PREVIOUS..$LATEST | grep -Eo '([A-Z]{3,}-)([0-9]+)' | sort -u) \
        <(git log $EARLIEST..$PREVIOUS | grep -Eo '([A-Z]{3,}-)([0-9]+)' | sort -u)
fi;