#!/usr/bin/env bash
# Mirrors the Kontur.OFD API docs sources (Sphinx `_sources/*.rst.txt`) into docs/api-reference/.
# The page list comes from the site's searchindex.js, so added and removed pages show up too.
# Used by .github/workflows/api-docs-watch.yml; can be run locally as well.
set -euo pipefail

base_uri="${KONTUR_OFD_DOCS_URI:-https://docs-ofd-api.kontur.ru}"
target_dir="${1:-$(dirname "$0")/../docs/api-reference}"

curl_get() {
    curl --fail --silent --show-error --location --retry 3 --retry-delay 5 "$@"
}

index="$(curl_get "$base_uri/searchindex.js")"
docnames="$(grep -o 'docnames:\[[^]]*\]' <<<"$index" | grep -o '"[^"]*"' | tr -d '"')"
if [ -z "$docnames" ]; then
    echo "No docnames found in $base_uri/searchindex.js, the site layout has probably changed." >&2
    exit 1
fi

staging="$(mktemp -d)"
trap 'rm -rf "$staging"' EXIT
while IFS= read -r name; do
    mkdir -p "$staging/$(dirname "$name")"
    curl_get "$base_uri/_sources/$name.rst.txt" --output "$staging/$name.rst"
done <<<"$docnames"

rm -rf "$target_dir"
mkdir -p "$target_dir"
cp -R "$staging/." "$target_dir/"
echo "Synced $(wc -l <<<"$docnames") pages from $base_uri into $target_dir"
