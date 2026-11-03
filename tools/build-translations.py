#!/usr/bin/env python3
"""
Rebuild the plugin's translation files.

    python3 tools/build-translations.py

Run from anywhere; paths are relative to this file. What it does:

1. Extracts all translatable strings from the PHP files (WordPress i18n functions)
   plus the plugin header (name, description, URLs) into languages/<domain>.pot.
2. Merges the template into every languages/<domain>-<locale>.po file. Existing
   translations are kept; new strings are added untranslated, removed strings are
   marked obsolete (#~) at the end of the file.
3. Compiles each .po into its .mo file.
4. Lists the strings that still need a translation.

To translate a new string: fill in its msgstr in the .po file (or open the .po in
Poedit) and run this script again to compile the .mo.

Requires the GNU gettext tools (xgettext, msgcat, msgmerge, msgfmt):
    brew install gettext
"""

from __future__ import annotations

import datetime
import re
import shutil
import subprocess
import sys
import tempfile
from pathlib import Path

PLUGIN_DIR = Path(__file__).resolve().parent.parent
MAIN_FILE = PLUGIN_DIR / "rsdev-custom-cart-settings.php"
LANG_DIR = PLUGIN_DIR / "languages"

# PHP files to scan, relative to the plugin folder.
SOURCE_GLOBS = ["*.php", "includes/**/*.php"]

# WordPress translation functions and where their strings/contexts are.
KEYWORDS = [
    "__", "_e", "esc_html__", "esc_html_e", "esc_attr__", "esc_attr_e",
    "_x:1,2c", "_ex:1,2c", "esc_html_x:1,2c", "esc_attr_x:1,2c",
    "_n:1,2", "_nx:1,2,4c", "_n_noop:1,2", "_nx_noop:1,2,3c",
]

# Plugin header fields that WordPress translates (shown in the Plugins list).
TRANSLATED_HEADERS = ["Plugin Name", "Plugin URI", "Update URI", "Description", "Author", "Author URI"]

TOOLS = ["xgettext", "msgcat", "msgmerge", "msgfmt"]


def fail(message: str) -> None:
    print(f"Error: {message}", file=sys.stderr)
    sys.exit(1)


def run(args: list[str]) -> str:
    result = subprocess.run(args, cwd=PLUGIN_DIR, capture_output=True, text=True)
    if result.returncode != 0:
        fail(f"{' '.join(args[:2])} failed:\n{result.stderr.strip()}")
    return result.stdout + result.stderr


def po_quote(text: str) -> str:
    return '"' + text.replace("\\", "\\\\").replace('"', '\\"').replace("\n", "\\n") + '"'


def read_plugin_header() -> dict[str, str]:
    """Read 'Field: value' lines from the plugin's header comment."""
    header_block = MAIN_FILE.read_text(encoding="utf-8")[:8192]
    fields = {}
    for match in re.finditer(r"^[ \t/*#@]*([A-Za-z][A-Za-z ]+?):[ \t]*(.+?)\s*$", header_block, re.M):
        fields.setdefault(match.group(1).strip(), match.group(2).strip())
    for required in ("Plugin Name", "Version", "Text Domain"):
        if required not in fields:
            fail(f"'{required}' not found in the header of {MAIN_FILE.name}")
    return fields


def build_header_pot(fields: dict[str, str], path: Path) -> None:
    """Write a .pot with the file header and the translatable plugin header fields."""
    plugin_uri = fields.get("Plugin URI", "")
    bugs = plugin_uri.rstrip("/") + "/issues" if "github.com/" in plugin_uri else plugin_uri
    now = datetime.datetime.now(datetime.timezone.utc).strftime("%Y-%m-%d %H:%M+0000")

    header = [
        f"# Copyright (C) {datetime.date.today().year} {fields.get('Author', '')}",
        f"# This file is distributed under the {fields.get('License', 'same license as the plugin')}.",
        'msgid ""',
        'msgstr ""',
        po_quote(f"Project-Id-Version: {fields['Plugin Name']} {fields['Version']}\n"),
        po_quote(f"Report-Msgid-Bugs-To: {bugs}\n"),
        po_quote(f"POT-Creation-Date: {now}\n"),
        po_quote("PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"),
        po_quote("Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"),
        po_quote("Language-Team: LANGUAGE <LL@li.org>\n"),
        po_quote("MIME-Version: 1.0\n"),
        po_quote("Content-Type: text/plain; charset=UTF-8\n"),
        po_quote("Content-Transfer-Encoding: 8bit\n"),
        po_quote(f"X-Domain: {fields['Text Domain']}\n"),
    ]

    # One entry per distinct value; fields with the same value (e.g. Plugin URI and
    # Update URI) share an entry, otherwise msgcat would see duplicates.
    entries: dict[str, list[str]] = {}
    for name in TRANSLATED_HEADERS:
        if fields.get(name):
            entries.setdefault(fields[name], []).append(name)

    blocks = ["\n".join(header)]
    for value, names in entries.items():
        comments = "\n".join(f"#. {name} of the plugin" for name in names)
        blocks.append(f"{comments}\n#: {MAIN_FILE.name}\nmsgid {po_quote(value)}\nmsgstr \"\"")

    path.write_text("\n\n".join(blocks) + "\n", encoding="utf-8")


def main() -> None:
    missing = [tool for tool in TOOLS if not shutil.which(tool)]
    if missing:
        fail(f"missing {', '.join(missing)}. Install the gettext tools with: brew install gettext")

    fields = read_plugin_header()
    domain = fields["Text Domain"]
    pot_path = LANG_DIR / f"{domain}.pot"

    sources = sorted({str(p.relative_to(PLUGIN_DIR)) for glob in SOURCE_GLOBS for p in PLUGIN_DIR.glob(glob)})
    if not sources:
        fail("no PHP files found")

    with tempfile.TemporaryDirectory() as tmp:
        header_pot = Path(tmp) / "header.pot"
        code_pot = Path(tmp) / "code.pot"

        build_header_pot(fields, header_pot)

        run(["xgettext", "--language=PHP", "--from-code=UTF-8", "--add-comments=translators:",
             "--force-po", *[f"--keyword={k}" for k in KEYWORDS], "-o", str(code_pot), *sources])
        # xgettext leaves the charset as a placeholder; msgcat needs a real one.
        code_pot.write_text(code_pot.read_text(encoding="utf-8").replace("charset=CHARSET", "charset=UTF-8"), encoding="utf-8")

        # Header file first, so its file header wins and shared strings are merged.
        run(["msgcat", "--use-first", "-o", str(pot_path), str(header_pot), str(code_pot)])

    template_count = run(["msgfmt", "--statistics", "-o", "/dev/null", str(pot_path)])
    print(f"Template: {pot_path.relative_to(PLUGIN_DIR)}  ({template_count.strip().split(',')[-1].strip()})")

    po_files = sorted(LANG_DIR.glob(f"{domain}-*.po"))
    if not po_files:
        print("No .po files found; only the template was updated.")
        return

    for po in po_files:
        run(["msgmerge", "--quiet", "--update", "--backup=none", "--no-fuzzy-matching", str(po), str(pot_path)])

        # Keep the version in the .po header in step with the plugin.
        text = po.read_text(encoding="utf-8")
        text = re.sub(r'"Project-Id-Version: [^"\\]*\\n"',
                      f'"Project-Id-Version: {fields["Plugin Name"]} {fields["Version"]}\\\\n"', text, count=1)
        po.write_text(text, encoding="utf-8")

        mo = po.with_suffix(".mo")
        stats = run(["msgfmt", "--check", "--statistics", "-o", str(mo), str(po)]).strip()
        print(f"{po.name}: {stats}")

        untranslated = run(["msgattrib", "--untranslated", "--no-obsolete", str(po)]) if shutil.which("msgattrib") else ""
        for msgid in untranslated_msgids(untranslated):
            # URLs and the author name don't need translating.
            if not re.fullmatch(r"https?://\S+", msgid) and msgid != fields.get("Author"):
                print(f"  needs translation: {msgid}")


def untranslated_msgids(po_text: str) -> list[str]:
    """msgids from msgattrib output, joining strings that span several lines."""
    msgids = []
    for block in po_text.split("\n\n"):
        match = re.search(r'^msgid ((?:".*"\n?)+)', block, re.M)
        if match:
            msgid = "".join(re.findall(r'"(.*)"', match.group(1)))
            if msgid:
                msgids.append(msgid)
    return msgids


if __name__ == "__main__":
    main()
