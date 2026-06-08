#!/usr/bin/env bash
# ------------------------------------------------------------------------
# Render the German and English branchupload user manuals to PDF.
#
# Requirements:
#   - pandoc      (brew install pandoc)
#   - weasyprint  (brew install weasyprint)
#
# Output (alongside the .md sources):
#   docs/user-manual/Benutzerhandbuch.pdf
#   docs/user-manual/UserManual.pdf
#
# Both PDFs are styled with docs/user-manual/style/eledia.css to match
# the eLeDia GmbH corporate identity (https://eledia.de).
#
# Usage:
#   ./docs/user-manual/build-pdf.sh        # build both
#   ./docs/user-manual/build-pdf.sh de     # build only German
#   ./docs/user-manual/build-pdf.sh en     # build only English
# ------------------------------------------------------------------------
set -euo pipefail

HERE="$(cd "$(dirname "$0")" && pwd)"
STYLE="$HERE/style/eledia.css"

if ! command -v pandoc >/dev/null 2>&1; then
    echo "✗ pandoc not found. Install it first: brew install pandoc" >&2
    exit 1
fi
if ! command -v weasyprint >/dev/null 2>&1; then
    echo "✗ weasyprint not found. Install it first: brew install weasyprint" >&2
    exit 1
fi

build_pdf() {
    local lang="$1"
    local md="$2"
    local pdf="$3"
    local cover="$4"

    echo "→ Building $pdf …"

    # Pandoc renders the markdown to a self-contained HTML5 fragment with a
    # generated table of contents. We then prepend the language-specific
    # cover page and hand the whole thing to WeasyPrint.
    local tmphtml
    tmphtml="$(mktemp -t branchupload-pdf.XXXXXX.html)"
    trap 'rm -f "$tmphtml"' RETURN

    pandoc "$md" \
        --from gfm+yaml_metadata_block+raw_html \
        --to html5 \
        --standalone \
        --toc --toc-depth=3 \
        --metadata "lang=$lang" \
        --css="$STYLE" \
        --include-before-body="$cover" \
        --output "$tmphtml"

    weasyprint "$tmphtml" "$pdf"
    rm -f "$tmphtml"
    trap - RETURN

    echo "✓ Wrote $pdf ($(du -h "$pdf" | cut -f1))"
}

target="${1:-all}"

case "$target" in
    de|all)
        build_pdf de \
            "$HERE/Benutzerhandbuch.md" \
            "$HERE/Benutzerhandbuch.pdf" \
            "$HERE/style/cover-de.html"
        ;;
esac

case "$target" in
    en|all)
        build_pdf en \
            "$HERE/UserManual.md" \
            "$HERE/UserManual.pdf" \
            "$HERE/style/cover-en.html"
        ;;
esac

case "$target" in
    de|en|all) ;;
    *)
        echo "Unknown target: $target (use 'de', 'en' or omit for both)" >&2
        exit 2
        ;;
esac
