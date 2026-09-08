---
name: Паломатика — банк алгебры
description: Dark navy workspace for selecting mathematical tasks and assembling lessons.
colors:
  bg: "#0e1724"
  panel: "#152235"
  panel2: "#1b2b40"
  line: "#314156"
  text: "#edf2f8"
  muted: "#a8b8cc"
  blue: "#9ac9ff"
  accent: "#b9dcff"
  ink: "#14263a"
  green: "#8adebe"
typography:
  headline:
    fontFamily: "Golos, sans-serif"
    fontSize: "29px"
    fontWeight: 650
    lineHeight: 1.2
    letterSpacing: "-0.025em"
  title:
    fontFamily: "Golos, sans-serif"
    fontSize: "18px"
    fontWeight: 700
    lineHeight: 1.5
    letterSpacing: "-0.02em"
  body:
    fontFamily: "Golos, sans-serif"
    fontSize: "14px"
    fontWeight: 400
    lineHeight: 1.5
  label:
    fontFamily: "Golos, sans-serif"
    fontSize: "12px"
    fontWeight: 400
    lineHeight: 1.5
rounded:
  control: "7px"
  inset: "8px"
  panel: "12px"
spacing:
  compact: "6px"
  small: "8px"
  control: "12px"
  inset: "16px"
  section: "24px"
components:
  button-primary:
    backgroundColor: "{colors.accent}"
    textColor: "{colors.ink}"
    rounded: "{rounded.control}"
    padding: "8px 13px"
  button-secondary:
    backgroundColor: "{colors.panel2}"
    textColor: "{colors.text}"
    rounded: "{rounded.control}"
    padding: "8px 13px"
  input:
    backgroundColor: "{colors.panel}"
    textColor: "{colors.text}"
    rounded: "{rounded.control}"
    padding: "9px 11px"
  inset-panel:
    backgroundColor: "{colors.panel}"
    rounded: "{rounded.panel}"
    padding: "16px"
---

# Design System: Паломатика — банк алгебры

## Overview

**Creative North Star: "Operate"**

This standalone preview extends the incumbent dark navy Palomatika bank. It is a dense teaching workspace: quiet surfaces frame readable mathematical expressions and compact controls. Pale blue identifies actions and selected filters; muted text carries supporting information.

The visual system is recorded from `index.html`, `style.css`, and `app.mjs`. It describes this preview, without establishing a new brand identity for the wider product.

**Key Characteristics:**

- Flat navy surfaces separated by fine borders.
- Compact Golos interface text alongside larger KaTeX mathematics.
- Pale blue action emphasis and mint collection confirmation.
- Responsive navigation and a collection that becomes a dialog.

## Colors

The palette combines dark blue surfaces with cool, high-lightness text and accents; frontmatter preserves the source CSS custom-property names.

### Primary

- **Pale Action Blue** (`accent`): primary print and collection buttons, toast background.
- **Clear Link Blue** (`blue`): links, focus outlines, level markers, selected filter borders, collection numbering, and the first plotted curve.
- **Deep Blue Ink** (`ink`): text on pale primary controls and toasts.

### Secondary

- **Mint Confirmation** (`green`): revealed answers, selected collection actions, and the second plotted curve.
- Warm amber appears on level-six badges and teacher-reviewed explanation markers. It is a narrow status treatment, not a general action color.

### Neutral

- **Night Navy** (`bg`): page and header background.
- **Panel Navy** (`panel`): inputs, combination panel, and revealed explanations.
- **Raised Navy Tone** (`panel2`): secondary controls and removable skill chips.
- **Slate Divider** (`line`): boundaries, task separators, and graph grid.
- **Cool White** (`text`): primary interface text.
- **Soft Slate** (`muted`): labels, descriptions, metadata, and solution prose.

**The State Has Text Rule.** Selection is expressed with a label or pressed state as well as color; collection actions change from «В подборку» to «В подборке».

## Typography

**Display Font:** Golos, with sans-serif fallback; no separate display face.
**Body Font:** Golos, with sans-serif fallback.
**Mathematical Typesetting:** self-hosted KaTeX with HTML and MathML output.

Golos is self-hosted in Cyrillic and Latin WOFF2 files with declared variable weights (100–900). The interface uses a compact hierarchy; equations receive more space and larger typesetting than metadata.

### Hierarchy

- **Headline:** frontmatter headline role; reduced to (25px) at mobile width.
- **Title:** frontmatter title role for collection and empty-state headings.
- **Subheading:** medium (15px, weight 550) for smaller headings.
- **Body:** frontmatter body role; task conditions use a looser line height (1.7) and maximum width (75ch).
- **Label:** frontmatter label role; supporting metadata commonly steps down to (10–11px).
- **Formula:** container size (21px), reduced to (19px) on mobile; KaTeX within uses (1.1em). Wide expressions scroll inside their container.

**The Formula Space Rule.** Keep mathematical expressions in their dedicated overflow container rather than shrinking the entire task to accommodate them.

## Layout

The desktop workspace has three columns: taxonomy (240px), flexible tasks (minimum 360px), and collection (302px), within a maximum width (1900px). Main content has padding (32px 28px 50px). The header is (76px) tall. Side rails are sticky and independently scrollable at viewport height.

At widths of at least (1600px), columns become (260px / flexible minimum 400px / 330px) and main horizontal padding becomes (45px). At widths up to (1250px), the layout becomes a taxonomy rail (215px) and flexible content; the collection moves into a fixed right dialog (340px, maximum 100vw).

At widths up to (760px), the header becomes (67px), the taxonomy becomes a horizontal scrolling row, and main padding becomes (24px 17px 50px). Class and skill selectors share a row; the class selector has the final stylesheet width (128px), and the combination action occupies its own row. The collection dialog fills the viewport width. Level controls retain their numbers while descriptive spans are hidden.

Spacing is compact around controls and more generous between tasks. Results appear as divided rows rather than isolated floating cards; desktop task rows have vertical padding (23px). The list displays up to 18 tasks per page.

Printing uses a separate white sheet, dark text, page margins (17mm), and task-level page-break avoidance. An optional answer key begins on a new page. The interface chrome is hidden.

## Elevation & Depth

Resting surfaces are flat, with no card shadows. Tone changes and one-pixel dividers establish boundaries. The collection dialog is the only raised panel, using a leftward shadow to distinguish its temporary overlay position. There is no backdrop blur.

### Shadow Vocabulary

- **Collection overlay:** `box-shadow: -12px 0 40px #0007`, active in the layout below the desktop collection breakpoint.

**The Flat Workspace Rule.** Preserve border and tone separation for persistent panels; reserve the existing shadow treatment for the overlay collection.

Task rows enter with a short opacity and vertical-position animation (220ms ease-out, 4px travel). This animation runs only when reduced motion is not requested. Button state changes have no transition in the current implementation.

## Shapes

Controls use the frontmatter control radius; combination panels use the panel radius, and answer insets use the inset radius. Level badges are small outlined rectangles (4px radius). Task rows have straight bottom dividers and no rounded enclosing box. Graphs retain a rectangular border. Action icons are inline stroke SVGs using current color.

## Components

### Buttons

Compact, directly labeled controls. Primary and secondary variants use the frontmatter assignments; ordinary buttons have a minimum height (40px). Primary hover becomes a lighter blue (`#dbecff`); ordinary enabled hover uses a navy fill (`#263a52`). Text actions have transparent backgrounds and borders with blue text. Disabled controls use opacity (0.45) and a default cursor.

All interactive controls have a keyboard focus outline (2px, blue token) offset by (3px). Collection add actions use a mint foreground, dark mint fill (`#1a3835`), and green-gray border (`#325a51`) when selected.

### Chips

Six numbered level controls plus «Все уровни» form a single pressed-state filter. Controls wrap, with minimum height (34px). The active level uses a blue border, dark blue fill (`#294261`), and pale text (`#e3f1ff`). Removable skill chips use the secondary panel tone and minimum height (32px); their SVG close indicator accompanies the skill name.

### Cards / Containers

Combination controls occupy a filled rounded panel with inset padding from the frontmatter. Revealed answers sit in a smaller rounded panel (13px 16px padding); the answer is mint, with muted explanatory prose below. Task results themselves are open rows separated by borders.

### Inputs / Fields

Native inputs and selects use the line border and panel background, with minimum height (42px). Search has height (45px), a stroke search icon, and left padding (42px). Visible labels remain above selectors. Placeholder text uses the muted token at full opacity. The lesson title input has a transparent fill.

### Navigation

Taxonomy buttons have muted text, transparent resting fills, and a filled active treatment (`#253a53`). Desktop counts use tabular numerals. On mobile, taxonomy buttons become single-line scrolling choices and counts disappear. A keyboard-visible skip link leads to task content.

### Task and Collection

Each task places level and skill metadata above the condition and formula. A native details disclosure reveals the answer and explanation. Variant and collection actions sit alongside it and wrap on narrow screens. Additional skill links add all-required search conditions; the combination panel states that all selected skills must occur in the same task.

The collection is an ordered compact list with move-up and remove controls. Empty states explain how to begin; output actions are disabled until tasks exist. Selection and title persist in localStorage. Printing, JSON export, plain-text copy, and a student sheet link are available. The narrow-screen dialog makes surrounding content inert, traps keyboard focus, closes with Escape, and restores focus to the opener.

## Do's and Don'ts

### Do:

- **Do** use navy tones and fine dividers for persistent workspace regions.
- **Do** retain independent formula overflow and readable mathematical typesetting.
- **Do** pair selected color treatments with text and pressed-state semantics.
- **Do** preserve horizontal mobile taxonomy and keyboard-operable collection behavior.
- **Do** provide a separate light print presentation for worksheets.

### Don't:

- **Don't** turn task rows into elevated cards when extending this preview.
- **Don't** use mint confirmation styling as the default primary action treatment.
- **Don't** hide all level identifiers when compacting the mobile filters.
- **Don't** imply that local collection persistence sends a lesson to palomatika.ru.
