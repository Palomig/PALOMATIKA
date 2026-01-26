#!/usr/bin/env python3
"""
Generate SVG diagrams for EGE Topic 01 (Planimetry)
This script creates SVG for all 216 tasks based on their geometry type.
"""

import json
import math
from pathlib import Path

# Color scheme matching OGE style
COLORS = {
    'bg': '#0a1628',
    'line': '#c8dce8',
    'highlight': '#d4a855',
    'auxiliary': '#5a9fcf',
    'point': '#7eb8da',
    'text': '#c8dce8',
    'green': '#10b981',
    'red': '#dc2626',
}

def point_marker(x, y):
    """Generate point marker SVG"""
    return f'''<g transform="translate({x}, {y})">
    <circle cx="0" cy="0" r="3" fill="{COLORS['point']}" />
  </g>'''

def label(x, y, text, color=None, size=14):
    """Generate text label"""
    c = color or COLORS['text']
    return f'<text x="{x}" y="{y}" fill="{c}" font-size="{size}" font-family="\'Times New Roman\', serif" font-style="italic" font-weight="500" text-anchor="middle" dominant-baseline="middle" class="geo-label">{text}</text>'

def line(x1, y1, x2, y2, color=None, width=1.5, dashed=False):
    """Generate line SVG"""
    c = color or COLORS['line']
    dash = ' stroke-dasharray="6,3"' if dashed else ''
    return f'<line x1="{x1}" y1="{y1}" x2="{x2}" y2="{y2}" stroke="{c}" stroke-width="{width}"{dash}/>'

def polygon(points, fill="none", stroke=None, width=1.5):
    """Generate polygon SVG"""
    s = stroke or COLORS['line']
    pts = " ".join([f"{p[0]},{p[1]}" for p in points])
    return f'<polygon points="{pts}" fill="{fill}" stroke="{s}" stroke-width="{width}" stroke-linejoin="round"/>'

def circle_shape(cx, cy, r, fill="none", stroke=None, width=1.5):
    """Generate circle SVG"""
    s = stroke or COLORS['line']
    return f'<circle cx="{cx}" cy="{cy}" r="{r}" fill="{fill}" stroke="{s}" stroke-width="{width}"/>'

def arc(cx, cy, r, start_angle, end_angle, color=None, width=1.2):
    """Generate arc SVG path"""
    c = color or COLORS['highlight']
    # Convert to radians
    a1 = math.radians(start_angle)
    a2 = math.radians(end_angle)

    x1 = cx + r * math.cos(a1)
    y1 = cy + r * math.sin(a1)
    x2 = cx + r * math.cos(a2)
    y2 = cy + r * math.sin(a2)

    # Determine if arc should be large
    diff = end_angle - start_angle
    large_arc = 1 if abs(diff) > 180 else 0
    sweep = 1 if diff > 0 else 0

    return f'<path d="M {x1} {y1} A {r} {r} 0 {large_arc} {sweep} {x2} {y2}" fill="none" stroke="{c}" stroke-width="{width}"/>'

def angle_arc(vertex, p1, p2, radius=20, color=None):
    """Generate angle arc between two lines from vertex"""
    c = color or COLORS['highlight']
    # Calculate angles
    angle1 = math.atan2(p1[1] - vertex[1], p1[0] - vertex[0])
    angle2 = math.atan2(p2[1] - vertex[1], p2[0] - vertex[0])

    x1 = vertex[0] + radius * math.cos(angle1)
    y1 = vertex[1] + radius * math.sin(angle1)
    x2 = vertex[0] + radius * math.cos(angle2)
    y2 = vertex[1] + radius * math.sin(angle2)

    # Calculate sweep direction
    diff = angle2 - angle1
    while diff > math.pi: diff -= 2 * math.pi
    while diff < -math.pi: diff += 2 * math.pi
    sweep = 1 if diff > 0 else 0

    return f'<path d="M {x1} {y1} A {radius} {radius} 0 0 {sweep} {x2} {y2}" fill="none" stroke="{c}" stroke-width="1.2"/>'

def right_angle(vertex, p1, p2, size=12):
    """Generate right angle marker"""
    angle1 = math.atan2(p1[1] - vertex[1], p1[0] - vertex[0])
    angle2 = math.atan2(p2[1] - vertex[1], p2[0] - vertex[0])

    c1 = (vertex[0] + size * math.cos(angle1), vertex[1] + size * math.sin(angle1))
    c2 = (vertex[0] + size * math.cos(angle2), vertex[1] + size * math.sin(angle2))
    diag = (c1[0] + size * math.cos(angle2), c1[1] + size * math.sin(angle2))

    return f'<path d="M {c1[0]} {c1[1]} L {diag[0]} {diag[1]} L {c2[0]} {c2[1]}" fill="none" stroke="#666666" stroke-width="1"/>'

def label_pos(point, center, distance=20):
    """Calculate label position away from center"""
    dx = point[0] - center[0]
    dy = point[1] - center[1]
    length = math.sqrt(dx*dx + dy*dy)
    if length == 0:
        return (point[0], point[1] - distance)
    return (point[0] + (dx/length) * distance, point[1] + (dy/length) * distance)

def midpoint(p1, p2):
    """Calculate midpoint"""
    return ((p1[0] + p2[0]) / 2, (p1[1] + p2[1]) / 2)

def centroid(points):
    """Calculate centroid of polygon"""
    x = sum(p[0] for p in points) / len(points)
    y = sum(p[1] for p in points) / len(points)
    return (x, y)

def wrap_svg(content, viewbox="0 0 220 180"):
    """Wrap content in SVG element"""
    return f'<svg viewBox="{viewbox}" class="w-full max-w-[250px] h-auto mx-auto"><rect width="100%" height="100%" fill="{COLORS["bg"]}"/>{content}</svg>'


# ============== SVG GENERATORS FOR EACH ZADANIE TYPE ==============

def svg_isosceles_triangle():
    """Isosceles triangle ABC with AC=BC (zadaniya 1-8)"""
    A = (30, 150)
    B = (190, 150)
    C = (110, 35)
    center = centroid([A, B, C])

    content = polygon([A, B, C])
    # Equality marks on AC and BC
    content += line(70, 92, 74, 88, COLORS['auxiliary'], 2)  # mark on AC
    content += line(146, 88, 150, 92, COLORS['auxiliary'], 2)  # mark on BC
    # Angle arc at C
    content += angle_arc(C, A, B, 25)
    # Labels
    lA = label_pos(A, center, 18)
    lB = label_pos(B, center, 18)
    lC = label_pos(C, center, 18)
    content += label(lA[0], lA[1], "A")
    content += label(lB[0], lB[1], "B")
    content += label(lC[0], lC[1], "C")

    return wrap_svg(content)

def svg_external_angle():
    """Isosceles triangle with external angle at B (zadaniya 9-12)"""
    A = (30, 140)
    B = (170, 140)
    C = (100, 40)
    center = centroid([A, B, C])

    content = polygon([A, B, C])
    # External line from B
    content += line(B[0], B[1], 220, 140, COLORS['auxiliary'], 1, dashed=True)
    # Equality marks
    content += line(65, 90, 69, 86, COLORS['auxiliary'], 2)
    content += line(131, 86, 135, 90, COLORS['auxiliary'], 2)
    # External angle arc
    content += angle_arc(B, C, (220, 140), 25, COLORS['highlight'])
    # Labels
    lA = label_pos(A, center, 18)
    lC = label_pos(C, center, 18)
    content += label(lA[0], lA[1], "A")
    content += label(B[0] + 5, B[1] + 18, "B")
    content += label(lC[0], lC[1], "C")

    return wrap_svg(content)

def svg_right_triangle_median():
    """Right triangle with median from C (zadaniya 13-16)"""
    A = (30, 150)
    B = (190, 150)
    C = (190, 40)
    D = midpoint(A, B)  # Median to hypotenuse
    center = centroid([A, B, C])

    content = polygon([A, B, C])
    # Right angle at B
    content += right_angle(B, A, C, 12)
    # Median CD
    content += line(C[0], C[1], D[0], D[1], COLORS['green'], 1, dashed=True)
    content += point_marker(D[0], D[1])
    # Angle arc at C (ACD)
    content += angle_arc(C, A, D, 25, COLORS['highlight'])
    # Labels
    lA = label_pos(A, center, 18)
    lC = label_pos(C, center, 18)
    content += label(lA[0], lA[1], "A")
    content += label(B[0] + 12, B[1] + 12, "B")
    content += label(lC[0], lC[1], "C")
    content += label(D[0], D[1] + 15, "D", COLORS['green'], 12)

    return wrap_svg(content)

def svg_bisector_median_angle():
    """Right triangle with bisector CD and median CM (zadaniya 17-20, 25-28)"""
    A = (30, 150)
    B = (190, 150)
    C = (30, 40)
    M = midpoint(A, B)  # Median endpoint
    # Bisector from C to AB (45° line from right angle)
    D = (110, 150)  # Approximate bisector endpoint
    center = centroid([A, B, C])

    content = polygon([A, B, C])
    # Right angle at C (between CA and CB)
    content += right_angle(C, A, B, 12)
    # Bisector CD
    content += line(C[0], C[1], D[0], D[1], COLORS['highlight'], 1, dashed=True)
    # Median CM
    content += line(C[0], C[1], M[0], M[1], COLORS['green'], 1, dashed=True)
    content += point_marker(D[0], D[1])
    content += point_marker(M[0], M[1])
    # Angle between CD and CM
    content += angle_arc(C, D, M, 30, COLORS['auxiliary'])
    # Labels
    lA = label_pos(A, center, 18)
    lB = label_pos(B, center, 18)
    content += label(lA[0], lA[1], "A")
    content += label(lB[0], lB[1], "B")
    content += label(C[0] - 15, C[1], "C")
    content += label(D[0], D[1] + 15, "D", COLORS['highlight'], 12)
    content += label(M[0], M[1] + 15, "M", COLORS['green'], 12)

    return wrap_svg(content)

def svg_height_bisector_angle():
    """Right triangle with height CH and bisector CD (zadaniya 21-24)"""
    A = (30, 150)
    B = (190, 150)
    C = (80, 40)
    # Height from C to AB
    H = (80, 150)
    # Bisector from C (45° from right angle vertex)
    D = (135, 150)
    center = centroid([A, B, C])

    content = polygon([A, B, C])
    # Right angle at C
    content += right_angle(C, A, B, 12)
    # Height CH
    content += line(C[0], C[1], H[0], H[1], COLORS['green'], 1, dashed=True)
    content += right_angle(H, C, B, 10)
    # Bisector CD
    content += line(C[0], C[1], D[0], D[1], COLORS['highlight'], 1, dashed=True)
    content += point_marker(H[0], H[1])
    content += point_marker(D[0], D[1])
    # Labels
    lA = label_pos(A, center, 18)
    lB = label_pos(B, center, 18)
    content += label(lA[0], lA[1], "A")
    content += label(lB[0], lB[1], "B")
    content += label(C[0] - 12, C[1] - 5, "C")
    content += label(H[0] - 12, H[1] + 12, "H", COLORS['green'], 12)
    content += label(D[0], D[1] + 15, "D", COLORS['highlight'], 12)

    return wrap_svg(content)

def svg_height_median_angle():
    """Right triangle with height CH and median CM (zadaniya 29-34)"""
    A = (30, 150)
    B = (190, 150)
    C = (30, 40)
    H = (30, 150)  # Height foot (same as A for right angle at C)
    M = midpoint(A, B)
    center = centroid([A, B, C])

    content = polygon([A, B, C])
    # Right angle at C
    content += right_angle(C, A, B, 12)
    # Height CH (vertical)
    content += line(C[0], C[1], A[0], A[1], COLORS['green'], 1.5)
    # Median CM
    content += line(C[0], C[1], M[0], M[1], COLORS['auxiliary'], 1, dashed=True)
    content += point_marker(M[0], M[1])
    # Angle between height and median
    content += angle_arc(C, A, M, 25, COLORS['highlight'])
    # Labels
    lB = label_pos(B, center, 18)
    content += label(A[0] - 12, A[1] + 5, "A")
    content += label(lB[0], lB[1], "B")
    content += label(C[0] - 12, C[1] - 5, "C")
    content += label(M[0], M[1] + 15, "M", COLORS['auxiliary'], 12)

    return wrap_svg(content)

def svg_triangle_bisector():
    """Triangle with bisector AD (zadaniya 35-38)"""
    A = (30, 150)
    B = (190, 150)
    C = (120, 35)
    # Bisector from A to BC
    D = (155, 92)
    center = centroid([A, B, C])

    content = polygon([A, B, C])
    # Bisector AD
    content += line(A[0], A[1], D[0], D[1], COLORS['green'], 1, dashed=True)
    content += point_marker(D[0], D[1])
    # Angle arcs at A (showing bisector divides angle)
    content += angle_arc(A, C, D, 25, COLORS['highlight'])
    content += angle_arc(A, D, B, 30, COLORS['highlight'])
    # Labels
    lA = label_pos(A, center, 18)
    lB = label_pos(B, center, 18)
    lC = label_pos(C, center, 18)
    content += label(lA[0], lA[1], "A")
    content += label(lB[0], lB[1], "B")
    content += label(lC[0], lC[1], "C")
    content += label(D[0] + 12, D[1], "D", COLORS['green'], 12)

    return wrap_svg(content)

def svg_heights_intersection():
    """Triangle with heights BD and CE intersecting at O (zadaniya 39-42)"""
    A = (30, 150)
    B = (190, 150)
    C = (100, 35)
    # Heights
    D = (100, 150)  # Foot of height from C
    E = (65, 92)    # Foot of height from B (approx)
    O = (100, 95)   # Intersection
    center = centroid([A, B, C])

    content = polygon([A, B, C])
    # Height from B to AC
    content += line(B[0], B[1], E[0], E[1], COLORS['green'], 1, dashed=True)
    # Height from C to AB
    content += line(C[0], C[1], D[0], D[1], COLORS['auxiliary'], 1, dashed=True)
    content += point_marker(O[0], O[1])
    content += point_marker(D[0], D[1])
    content += point_marker(E[0], E[1])
    # Right angle marks
    content += right_angle(D, C, B, 8)
    content += right_angle(E, B, A, 8)
    # Angle DOE
    content += angle_arc(O, D, E, 20, COLORS['highlight'])
    # Labels
    lA = label_pos(A, center, 18)
    lB = label_pos(B, center, 18)
    lC = label_pos(C, center, 18)
    content += label(lA[0], lA[1], "A")
    content += label(lB[0], lB[1], "B")
    content += label(lC[0], lC[1], "C")
    content += label(D[0], D[1] + 15, "D", COLORS['auxiliary'], 12)
    content += label(E[0] - 12, E[1], "E", COLORS['green'], 12)
    content += label(O[0] + 12, O[1], "O", COLORS['highlight'], 12)

    return wrap_svg(content)

def svg_bisectors_intersection():
    """Triangle with bisectors AD and BE intersecting at O (zadaniya 43-46)"""
    A = (30, 150)
    B = (190, 150)
    C = (110, 35)
    # Bisector endpoints
    D = (150, 92)  # On BC
    E = (70, 92)   # On AC
    O = (110, 110) # Intersection
    center = centroid([A, B, C])

    content = polygon([A, B, C])
    # Bisector from A
    content += line(A[0], A[1], D[0], D[1], COLORS['green'], 1, dashed=True)
    # Bisector from B
    content += line(B[0], B[1], E[0], E[1], COLORS['auxiliary'], 1, dashed=True)
    content += point_marker(O[0], O[1])
    content += point_marker(D[0], D[1])
    content += point_marker(E[0], E[1])
    # Angle AOB
    content += angle_arc(O, A, B, 20, COLORS['highlight'])
    # Labels
    lA = label_pos(A, center, 18)
    lB = label_pos(B, center, 18)
    lC = label_pos(C, center, 18)
    content += label(lA[0], lA[1], "A")
    content += label(lB[0], lB[1], "B")
    content += label(lC[0], lC[1], "C")
    content += label(D[0] + 12, D[1], "D", COLORS['green'], 12)
    content += label(E[0] - 12, E[1], "E", COLORS['auxiliary'], 12)
    content += label(O[0], O[1] - 15, "O", COLORS['highlight'], 12)

    return wrap_svg(content)

def svg_parallelogram_angles():
    """Parallelogram ABCD (zadaniya 47-50)"""
    A = (30, 140)
    B = (180, 140)
    C = (210, 50)
    D = (60, 50)
    center = centroid([A, B, C, D])

    content = polygon([A, B, C, D])
    # Angle arcs
    content += angle_arc(A, D, B, 25, COLORS['highlight'])
    content += angle_arc(B, A, C, 25, COLORS['auxiliary'])
    # Labels
    content += label(A[0] - 12, A[1] + 10, "A")
    content += label(B[0] + 12, B[1] + 10, "B")
    content += label(C[0] + 12, C[1] - 10, "C")
    content += label(D[0] - 12, D[1] - 10, "D")

    return wrap_svg(content)

def svg_rhombus_angles():
    """Rhombus ABCD with diagonals (zadaniya 51-54)"""
    A = (30, 90)
    B = (110, 150)
    C = (190, 90)
    D = (110, 30)
    O = (110, 90)  # Center

    content = polygon([A, B, C, D])
    # Diagonals
    content += line(A[0], A[1], C[0], C[1], COLORS['auxiliary'], 1, dashed=True)
    content += line(B[0], B[1], D[0], D[1], COLORS['auxiliary'], 1, dashed=True)
    content += point_marker(O[0], O[1])
    # Angle arc
    content += angle_arc(A, D, B, 25, COLORS['highlight'])
    # Labels
    content += label(A[0] - 12, A[1], "A")
    content += label(B[0], B[1] + 15, "B")
    content += label(C[0] + 12, C[1], "C")
    content += label(D[0], D[1] - 15, "D")

    return wrap_svg(content)

def svg_parallelogram_heights():
    """Parallelogram with heights (zadaniya 55-58)"""
    A = (20, 130)
    B = (160, 130)
    C = (200, 50)
    D = (60, 50)
    # Heights
    H1 = (60, 130)  # Height to AB
    H2 = (20, 50)   # Approx

    content = polygon([A, B, C, D])
    # Height lines
    content += line(D[0], D[1], H1[0], H1[1], COLORS['green'], 1, dashed=True)
    content += right_angle(H1, D, B, 8)
    content += point_marker(H1[0], H1[1])
    # Labels
    content += label(A[0] - 10, A[1] + 12, "A")
    content += label(B[0] + 10, B[1] + 12, "B")
    content += label(C[0] + 10, C[1] - 10, "C")
    content += label(D[0] - 10, D[1] - 10, "D")
    content += label(95, 90, "h", COLORS['green'], 12)

    return wrap_svg(content)

def svg_trapezoid_in_parallelogram():
    """Parallelogram with midpoint forming trapezoid (zadaniya 59-62)"""
    A = (20, 130)
    B = (160, 130)
    C = (200, 50)
    D = (60, 50)
    F = midpoint(C, D)  # Midpoint of CD

    content = polygon([A, B, C, D])
    # Trapezoid ABCF highlighted
    content += line(A[0], A[1], F[0], F[1], COLORS['auxiliary'], 1, dashed=True)
    content += point_marker(F[0], F[1])
    # Labels
    content += label(A[0] - 10, A[1] + 12, "A")
    content += label(B[0] + 10, B[1] + 12, "B")
    content += label(C[0] + 10, C[1] - 10, "C")
    content += label(D[0] - 10, D[1] - 10, "D")
    content += label(F[0], F[1] - 15, "F", COLORS['auxiliary'], 12)

    return wrap_svg(content)

def svg_triangle_heights():
    """Triangle with two heights (zadaniya 63-66)"""
    A = (30, 150)
    B = (190, 150)
    C = (100, 40)
    H1 = (100, 150)  # Height from C
    center = centroid([A, B, C])

    content = polygon([A, B, C])
    # Heights
    content += line(C[0], C[1], H1[0], H1[1], COLORS['green'], 1, dashed=True)
    content += right_angle(H1, C, B, 8)
    content += point_marker(H1[0], H1[1])
    # Side labels
    content += label(60, 85, "a", COLORS['auxiliary'], 12)
    content += label(150, 85, "b", COLORS['auxiliary'], 12)
    content += label(110, 158, "c", COLORS['auxiliary'], 12)
    # Labels
    lA = label_pos(A, center, 18)
    lB = label_pos(B, center, 18)
    lC = label_pos(C, center, 18)
    content += label(lA[0], lA[1], "A")
    content += label(lB[0], lB[1], "B")
    content += label(lC[0], lC[1], "C")

    return wrap_svg(content)

def svg_isosceles_area():
    """Isosceles triangle for area calculation (zadaniya 67-70)"""
    A = (30, 150)
    B = (190, 150)
    C = (110, 40)
    center = centroid([A, B, C])

    content = polygon([A, B, C])
    # Equality marks
    content += line(68, 90, 72, 86, COLORS['auxiliary'], 2)
    content += line(148, 86, 152, 90, COLORS['auxiliary'], 2)
    # Angle at C (30°)
    content += angle_arc(C, A, B, 25, COLORS['highlight'])
    content += label(110, 70, "30°", COLORS['highlight'], 11)
    # Labels
    lA = label_pos(A, center, 18)
    lB = label_pos(B, center, 18)
    lC = label_pos(C, center, 18)
    content += label(lA[0], lA[1], "A")
    content += label(lB[0], lB[1], "B")
    content += label(lC[0], lC[1], "C")

    return wrap_svg(content)

def svg_equilateral_triangle():
    """Equilateral triangle with height (zadaniya 71-74)"""
    A = (30, 150)
    B = (190, 150)
    C = (110, 11)
    H = (110, 150)
    center = centroid([A, B, C])

    content = polygon([A, B, C])
    # Height CH
    content += line(C[0], C[1], H[0], H[1], COLORS['green'], 1, dashed=True)
    content += right_angle(H, C, B, 8)
    content += point_marker(H[0], H[1])
    # Equality marks (3 sides)
    content += line(68, 75, 72, 71, COLORS['auxiliary'], 2)
    content += line(148, 71, 152, 75, COLORS['auxiliary'], 2)
    content += line(108, 150, 112, 150, COLORS['auxiliary'], 2)
    # Labels
    lA = label_pos(A, center, 18)
    lB = label_pos(B, center, 18)
    content += label(lA[0], lA[1], "A")
    content += label(lB[0], lB[1], "B")
    content += label(C[0], C[1] - 15, "C")
    content += label(H[0], H[1] + 15, "H", COLORS['green'], 12)

    return wrap_svg(content)

def svg_midline_triangle():
    """Triangle with midline DE (zadaniya 75-82)"""
    A = (30, 150)
    B = (190, 150)
    C = (110, 35)
    D = midpoint(A, C)
    E = midpoint(B, C)
    center = centroid([A, B, C])

    content = polygon([A, B, C])
    # Midline DE
    content += line(D[0], D[1], E[0], E[1], COLORS['green'], 1.5)
    content += point_marker(D[0], D[1])
    content += point_marker(E[0], E[1])
    # Labels
    lA = label_pos(A, center, 18)
    lB = label_pos(B, center, 18)
    lC = label_pos(C, center, 18)
    content += label(lA[0], lA[1], "A")
    content += label(lB[0], lB[1], "B")
    content += label(lC[0], lC[1], "C")
    content += label(D[0] - 12, D[1], "D", COLORS['green'], 12)
    content += label(E[0] + 12, E[1], "E", COLORS['green'], 12)

    return wrap_svg(content)

def svg_trapezoid_midline():
    """Trapezoid with midline and diagonal (zadaniya 83-86)"""
    A = (30, 140)
    B = (190, 140)
    C = (160, 50)
    D = (60, 50)
    # Midline
    M1 = midpoint(A, D)
    M2 = midpoint(B, C)

    content = polygon([A, B, C, D])
    # Midline
    content += line(M1[0], M1[1], M2[0], M2[1], COLORS['green'], 1.5)
    # Diagonal
    content += line(A[0], A[1], C[0], C[1], COLORS['auxiliary'], 1, dashed=True)
    # Labels
    content += label(A[0] - 10, A[1] + 12, "A")
    content += label(B[0] + 10, B[1] + 12, "B")
    content += label(C[0] + 10, C[1] - 10, "C")
    content += label(D[0] - 10, D[1] - 10, "D")

    return wrap_svg(content)

def svg_isosceles_cosine():
    """Isosceles triangle with cosine calculation (zadaniya 87-90)"""
    A = (30, 140)
    B = (190, 140)
    C = (110, 40)
    center = centroid([A, B, C])

    content = polygon([A, B, C])
    # Equality marks
    content += line(68, 85, 72, 81, COLORS['auxiliary'], 2)
    content += line(148, 81, 152, 85, COLORS['auxiliary'], 2)
    # Angle at A
    content += angle_arc(A, C, B, 25, COLORS['highlight'])
    # Labels
    lA = label_pos(A, center, 18)
    lB = label_pos(B, center, 18)
    lC = label_pos(C, center, 18)
    content += label(lA[0], lA[1], "A")
    content += label(lB[0], lB[1], "B")
    content += label(lC[0], lC[1], "C")

    return wrap_svg(content)

def svg_right_triangle_trig():
    """Right triangle for trigonometry (zadaniya 91-112)"""
    A = (30, 150)
    B = (190, 150)
    C = (190, 50)
    center = centroid([A, B, C])

    content = polygon([A, B, C])
    # Right angle at B
    content += right_angle(B, A, C, 12)
    # Angle at A or B
    content += angle_arc(A, B, C, 25, COLORS['highlight'])
    # Labels
    lA = label_pos(A, center, 18)
    lC = label_pos(C, center, 18)
    content += label(lA[0], lA[1], "A")
    content += label(B[0] + 12, B[1] + 12, "B")
    content += label(lC[0], lC[1], "C")

    return wrap_svg(content)

def svg_isosceles_height_trig():
    """Isosceles triangle with height for trig (zadaniya 113-128)"""
    A = (30, 150)
    B = (190, 150)
    C = (110, 40)
    H = (110, 150)
    center = centroid([A, B, C])

    content = polygon([A, B, C])
    # Height CH
    content += line(C[0], C[1], H[0], H[1], COLORS['green'], 1, dashed=True)
    content += right_angle(H, C, B, 8)
    content += point_marker(H[0], H[1])
    # Equality marks
    content += line(68, 90, 72, 86, COLORS['auxiliary'], 2)
    content += line(148, 86, 152, 90, COLORS['auxiliary'], 2)
    # Angle at A
    content += angle_arc(A, C, B, 25, COLORS['highlight'])
    # Labels
    lA = label_pos(A, center, 18)
    lB = label_pos(B, center, 18)
    lC = label_pos(C, center, 18)
    content += label(lA[0], lA[1], "A")
    content += label(lB[0], lB[1], "B")
    content += label(lC[0], lC[1], "C")
    content += label(H[0], H[1] + 15, "H", COLORS['green'], 12)

    return wrap_svg(content)

def svg_central_inscribed_angles():
    """Circle with central and inscribed angles (zadaniya 129-136)"""
    O = (110, 95)
    r = 70
    A = (110, 25)   # Top
    B = (40, 115)   # Left
    C = (180, 115)  # Right (on circle)

    content = circle_shape(O[0], O[1], r)
    content += point_marker(O[0], O[1])
    content += point_marker(A[0], A[1])
    content += point_marker(B[0], B[1])
    content += point_marker(C[0], C[1])
    # Central angle AOB
    content += line(O[0], O[1], A[0], A[1], COLORS['auxiliary'], 1)
    content += line(O[0], O[1], B[0], B[1], COLORS['auxiliary'], 1)
    # Inscribed angle ACB
    content += line(A[0], A[1], C[0], C[1], COLORS['green'], 1)
    content += line(B[0], B[1], C[0], C[1], COLORS['green'], 1)
    # Angle arcs
    content += angle_arc(O, A, B, 20, COLORS['highlight'])
    content += angle_arc(C, A, B, 25, COLORS['green'])
    # Labels
    content += label(O[0] + 12, O[1] - 5, "O")
    content += label(A[0], A[1] - 12, "A")
    content += label(B[0] - 12, B[1], "B")
    content += label(C[0] + 12, C[1], "C")

    return wrap_svg(content)

def svg_diameters():
    """Circle with two diameters AC and BD (zadaniya 137-144)"""
    O = (110, 95)
    r = 65
    A = (45, 95)
    C = (175, 95)
    B = (110, 30)
    D = (110, 160)

    content = circle_shape(O[0], O[1], r)
    content += point_marker(O[0], O[1])
    # Diameters
    content += line(A[0], A[1], C[0], C[1], COLORS['line'], 1)
    content += line(B[0], B[1], D[0], D[1], COLORS['line'], 1)
    content += point_marker(A[0], A[1])
    content += point_marker(B[0], B[1])
    content += point_marker(C[0], C[1])
    content += point_marker(D[0], D[1])
    # Angle arc AOD
    content += angle_arc(O, A, D, 20, COLORS['highlight'])
    # Chord from A to B and inscribed angle ACB
    content += line(A[0], A[1], B[0], B[1], COLORS['green'], 1)
    content += line(B[0], B[1], C[0], C[1], COLORS['green'], 1)
    content += angle_arc(C, A, B, 20, COLORS['green'])
    # Labels
    content += label(O[0] - 12, O[1] + 12, "O")
    content += label(A[0] - 12, A[1], "A")
    content += label(B[0], B[1] - 12, "B")
    content += label(C[0] + 12, C[1], "C")
    content += label(D[0], D[1] + 12, "D")

    return wrap_svg(content)

def svg_inscribed_angle_arc():
    """Circle with inscribed angle on arc (zadaniya 145-152)"""
    O = (110, 95)
    r = 65
    A = (55, 55)
    B = (165, 55)
    C = (110, 160)

    content = circle_shape(O[0], O[1], r)
    content += point_marker(O[0], O[1])
    content += point_marker(A[0], A[1])
    content += point_marker(B[0], B[1])
    content += point_marker(C[0], C[1])
    # Inscribed angle
    content += line(A[0], A[1], C[0], C[1], COLORS['line'], 1)
    content += line(B[0], B[1], C[0], C[1], COLORS['line'], 1)
    content += angle_arc(C, A, B, 25, COLORS['highlight'])
    # Arc AB (highlighted)
    content += arc(O[0], O[1], r + 5, -130, -50, COLORS['green'], 2)
    # Labels
    content += label(O[0], O[1] + 15, "O")
    content += label(A[0] - 12, A[1] - 5, "A")
    content += label(B[0] + 12, B[1] - 5, "B")
    content += label(C[0], C[1] + 15, "C")

    return wrap_svg(content)

def svg_tangent_line():
    """Circle with tangent CA and secant CO (zadaniya 153-164)"""
    O = (130, 95)
    r = 55
    C = (30, 95)
    A = (75, 95)  # Point of tangency
    B = (185, 95)  # Other intersection

    content = circle_shape(O[0], O[1], r)
    content += point_marker(O[0], O[1])
    content += point_marker(C[0], C[1])
    content += point_marker(A[0], A[1])
    content += point_marker(B[0], B[1])
    # Tangent line (vertical through A)
    content += line(A[0], A[1] - 50, A[0], A[1] + 50, COLORS['green'], 1)
    # Secant line CA through O to B
    content += line(C[0], C[1], B[0], B[1], COLORS['line'], 1)
    # Right angle at A
    content += right_angle(A, C, (A[0], A[1] - 30), 8)
    # Angle ACO
    content += angle_arc(C, A, O, 20, COLORS['highlight'])
    # Labels
    content += label(O[0], O[1] - 15, "O")
    content += label(C[0] - 12, C[1], "C")
    content += label(A[0], A[1] + 18, "A")
    content += label(B[0] + 10, B[1], "B")

    return wrap_svg(content)

def svg_two_tangents():
    """Circle with two tangents from C (zadaniya 165-168)"""
    O = (110, 100)
    r = 50
    C = (110, 180)
    # Points of tangency
    A = (60, 100)
    B = (160, 100)

    content = circle_shape(O[0], O[1], r)
    content += point_marker(O[0], O[1])
    content += point_marker(C[0], C[1])
    content += point_marker(A[0], A[1])
    content += point_marker(B[0], B[1])
    # Tangent lines
    content += line(C[0], C[1], A[0], A[1], COLORS['line'], 1)
    content += line(C[0], C[1], B[0], B[1], COLORS['line'], 1)
    # Arc AB
    content += arc(O[0], O[1], r + 5, -180, 0, COLORS['green'], 2)
    # Angle ACB
    content += angle_arc(C, A, B, 25, COLORS['highlight'])
    # Labels
    content += label(O[0], O[1], "O")
    content += label(C[0], C[1] + 15, "C")
    content += label(A[0] - 12, A[1], "A")
    content += label(B[0] + 12, B[1], "B")

    return wrap_svg(content)

def svg_inscribed_circle_quad():
    """Quadrilateral with inscribed circle (zadaniya 173-180)"""
    A = (30, 150)
    B = (190, 150)
    C = (170, 40)
    D = (50, 40)
    O = (110, 95)
    r = 45

    content = polygon([A, B, C, D])
    content += circle_shape(O[0], O[1], r)
    content += point_marker(O[0], O[1])
    # Labels
    content += label(A[0] - 10, A[1] + 12, "A")
    content += label(B[0] + 10, B[1] + 12, "B")
    content += label(C[0] + 10, C[1] - 10, "C")
    content += label(D[0] - 10, D[1] - 10, "D")

    return wrap_svg(content)

def svg_inscribed_circle_trapezoid():
    """Right trapezoid with inscribed circle (zadaniya 181-188)"""
    A = (30, 150)
    B = (190, 150)
    C = (190, 50)
    D = (30, 50)
    O = (110, 100)
    r = 40

    content = polygon([A, B, C, D])
    content += circle_shape(O[0], O[1], r)
    content += point_marker(O[0], O[1])
    # Right angles
    content += right_angle(A, D, B, 10)
    content += right_angle(D, A, C, 10)
    # Labels
    content += label(A[0] - 10, A[1] + 12, "A")
    content += label(B[0] + 10, B[1] + 12, "B")
    content += label(C[0] + 10, C[1] - 10, "C")
    content += label(D[0] - 10, D[1] - 10, "D")

    return wrap_svg(content)

def svg_circumscribed_quad():
    """Quadrilateral inscribed in circle (zadaniya 189-212)"""
    O = (110, 95)
    r = 70
    # Points on circle
    A = (40, 95)
    B = (90, 30)
    C = (180, 95)
    D = (110, 165)

    content = circle_shape(O[0], O[1], r)
    content += polygon([A, B, C, D])
    content += point_marker(O[0], O[1])
    # Diagonal
    content += line(A[0], A[1], C[0], C[1], COLORS['auxiliary'], 1, dashed=True)
    content += line(B[0], B[1], D[0], D[1], COLORS['auxiliary'], 1, dashed=True)
    # Labels
    content += label(A[0] - 12, A[1], "A")
    content += label(B[0], B[1] - 12, "B")
    content += label(C[0] + 12, C[1], "C")
    content += label(D[0], D[1] + 15, "D")

    return wrap_svg(content)

def svg_sine_theorem():
    """Triangle with circumscribed circle (zadaniya 213-216)"""
    O = (110, 100)
    r = 60
    A = (50, 100)
    B = (170, 100)
    C = (110, 40)

    content = circle_shape(O[0], O[1], r)
    content += polygon([A, B, C])
    content += point_marker(O[0], O[1])
    # Angle at C
    content += angle_arc(C, A, B, 20, COLORS['highlight'])
    # Radius line
    content += line(O[0], O[1], C[0], C[1], COLORS['auxiliary'], 1, dashed=True)
    # Labels
    content += label(A[0] - 12, A[1], "A")
    content += label(B[0] + 12, B[1], "B")
    content += label(C[0], C[1] - 15, "C")
    content += label(O[0] + 12, O[1] + 12, "O")
    content += label(110, 160, "R", COLORS['green'], 12)

    return wrap_svg(content)


# Mapping zadanie numbers to SVG generators
SVG_GENERATORS = {
    1: svg_isosceles_triangle,           # Isosceles triangle angles
    2: svg_external_angle,               # External angle
    3: svg_right_triangle_median,        # Median in right triangle
    4: svg_bisector_median_angle,        # Bisector and median angle
    5: svg_height_bisector_angle,        # Height and bisector angle
    6: svg_bisector_median_angle,        # Bisector and median (reverse)
    7: svg_height_median_angle,          # Height and median angle
    8: svg_triangle_bisector,            # Triangle bisector
    9: svg_heights_intersection,         # Heights intersection
    10: svg_bisectors_intersection,      # Bisectors intersection
    11: svg_parallelogram_angles,        # Parallelogram angles
    12: svg_rhombus_angles,              # Rhombus angles
    13: svg_parallelogram_heights,       # Parallelogram heights
    14: svg_trapezoid_in_parallelogram,  # Trapezoid in parallelogram
    15: svg_triangle_heights,            # Triangle heights
    16: svg_isosceles_area,              # Isosceles area
    17: svg_equilateral_triangle,        # Equilateral triangle
    18: svg_midline_triangle,            # Midline triangle
    19: svg_trapezoid_midline,           # Trapezoid midline
    20: svg_isosceles_cosine,            # Isosceles cosine
    21: svg_right_triangle_trig,         # Right triangle trig
    22: svg_right_triangle_trig,         # Sine in right triangle
    23: svg_right_triangle_trig,         # Cosine by sides
    24: svg_right_triangle_trig,         # Sine of angle B
    25: svg_right_triangle_trig,         # Sine connection
    26: svg_right_triangle_trig,         # Hypotenuse through tangent
    27: svg_isosceles_height_trig,       # Isosceles with height
    28: svg_isosceles_height_trig,       # Sine in isosceles
    29: svg_central_inscribed_angles,    # Central and inscribed angles
    30: svg_diameters,                   # Circle diameters
    31: svg_inscribed_angle_arc,         # Inscribed angle on arc
    32: svg_inscribed_angle_arc,         # Arcs on circle
    33: svg_tangent_line,                # Tangent to circle
    34: svg_tangent_line,                # Arc AD through B and D
    35: svg_two_tangents,                # Two tangents
    36: svg_inscribed_angle_arc,         # Angles ACB and DAE
    37: svg_inscribed_circle_quad,       # Inscribed circle quadrilateral
    38: svg_inscribed_circle_quad,       # Perimeter circumscribed quad
    39: svg_inscribed_circle_trapezoid,  # Inscribed circle trapezoid
    40: svg_inscribed_circle_trapezoid,  # Midline circumscribed trapezoid
    41: svg_circumscribed_quad,          # Inscribed quadrilateral angle ABC
    42: svg_circumscribed_quad,          # Angle ABD
    43: svg_circumscribed_quad,          # Angle CAD
    44: svg_circumscribed_quad,          # Opposite angles
    45: svg_circumscribed_quad,          # Two angles
    46: svg_sine_theorem,                # Extended sine theorem
}


def main():
    # Read the original JSON
    json_path = Path('/home/user/PALOMATIKA/storage/app/tasks/ege/topic_01.json')
    with open(json_path, 'r', encoding='utf-8') as f:
        data = json.load(f)

    # Process each zadanie and add SVG to tasks
    total_tasks = 0
    for block in data['blocks']:
        for zadanie in block['zadaniya']:
            zadanie_num = zadanie['number']
            svg_generator = SVG_GENERATORS.get(zadanie_num)

            if svg_generator:
                svg_content = svg_generator()
                # Add SVG to each task in this zadanie
                for task in zadanie['tasks']:
                    task['svg'] = svg_content
                    total_tasks += 1
                print(f"✓ Zadanie {zadanie_num}: {len(zadanie['tasks'])} tasks")
            else:
                print(f"✗ No SVG generator for zadanie {zadanie_num}")

    # Write updated JSON
    with open(json_path, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=4)

    print(f"\n✅ Done! Added SVG to {total_tasks} tasks")
    print(f"📁 Updated: {json_path}")


if __name__ == '__main__':
    main()
