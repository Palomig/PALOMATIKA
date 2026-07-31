# Fraction Balance 3D Design

Goal: replace the existing fractions lesson with an interactive 3D balance that teaches equality-preserving operations.

The lesson uses `1/2 = 2/4` as the stable baseline. Students choose an operation and whether to apply it to both sides, the left side, or the right side. Applying the same operation to both sides keeps the 3D balance level; applying it to only one side tilts the beam and marks the operation as invalid.

The production surface is `/learn/fractions`. Three.js renders the balance, pans, fraction blocks, and tilt animation. Native HTML is layered over the canvas for the formula, operation controls, side selector, and lesson feedback, preserving selectable text and normal controls while the canvas carries the spatial model.

Test coverage: a feature test asserts the public lesson opens and includes the expected 3D balance hooks, labels, and JavaScript asset.
