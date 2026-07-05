#!/bin/sh -e
# Usage: new-project.sh <namespace> <project> [description]

: "${BUGZ_URL:?BUGZ_URL is not set}"
: "${SECRET:?SECRET is not set}"

namespace="${1:?usage: $0 <namespace> <project> [description]}"
project="${2:?usage: $0 <namespace> <project> [description]}"
description="${3:-}"

curl -fsS \
  -H "Authorization: Bearer $SECRET" \
  --data-urlencode "namespace=$namespace" \
  --data-urlencode "project_name=$project" \
  --data-urlencode "description=$description" \
  "$BUGZ_URL/api/project"
