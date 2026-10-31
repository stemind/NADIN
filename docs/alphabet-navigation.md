# Library alphabet — 31 October 2026

The library alphabet uses a compact horizontal strip with consistent letter sizes, hover/focus states and a selected-letter marker. The letters wrap on narrow screens without sideways scrolling. Only letters present in the library are shown. Alphabetical/newest sorting, print/export links, tune creation and mobile tune menus are retained.

This change is applied to the existing V8–V16 branches. Real Chromium coverage is included in `tests/ui-browser.cjs`; it checks link navigation, hover states, sort switching and layouts at 1440, 390 and 320 pixels. Run it with generated PHP library views, Playwright and an installed Chromium browser. `NADIN_CHROMIUM` can select a local executable.
