#!/bin/bash
set -x

GITBRANCH=$(git symbolic-ref --short HEAD 2>/dev/null || echo "")
GITCOMMIT=$(git rev-parse HEAD 2>/dev/null || echo "")
GITTAGS=$(git tag --points-at $(git rev-parse HEAD 2>/dev/null) || echo "")
TAGLIST=$(tr '\n' ',' <<< "$GITTAGS" | sed 's/,$//')

echo "    built:" $(date) > saas/services/base/config/motd
echo "    gittag: " ${GITTAGS:-none} >> saas/services/base/config/motd
echo "    commit: " $GITCOMMIT >> saas/services/base/config/motd
echo >> saas/services/base/config/motd
echo ${GITTAG:-$GITCOMMIT} > version.txt