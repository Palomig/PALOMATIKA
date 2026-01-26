#!/usr/bin/env python3
"""
Generate SVG diagrams for EGE Topic 02 (Vectors)
Creates coordinate planes with vector arrows
"""

import json
import math
import os

# Dark theme colors
COLORS = {
    'bg': '#0a0f1a',
    'grid': '#1a2332',
    'axis': '#4b5563',
    'vector_a': '#ef4444',  # Red
    'vector_b': '#3b82f6',  # Blue
    'vector_c': '#10b981',  # Green
    'text': '#e2e8f0',
    'label': '#94a3b8',
}

def create_arrow_marker(color, marker_id):
    """Create SVG marker for arrow head"""
    return f'''<marker id="{marker_id}" markerWidth="10" markerHeight="7"
               refX="9" refY="3.5" orient="auto" markerUnits="strokeWidth">
        <polygon points="0 0, 10 3.5, 0 7" fill="{color}"/>
    </marker>'''

def generate_vectors_svg(params, svg_type='vectors_2'):
    """Generate SVG for vectors on coordinate plane"""

    # Determine vectors
    vectors = []
    if 'a' in params:
        vectors.append(('a', params['a'], COLORS['vector_a']))
    if 'b' in params:
        vectors.append(('b', params['b'], COLORS['vector_b']))
    if 'c' in params:
        vectors.append(('c', params['c'], COLORS['vector_c']))

    # Calculate bounds
    all_points = []
    for name, vec, color in vectors:
        all_points.append(vec['start'])
        all_points.append(vec['end'])

    min_x = min(p[0] for p in all_points) - 1
    max_x = max(p[0] for p in all_points) + 1
    min_y = min(p[1] for p in all_points) - 1
    max_y = max(p[1] for p in all_points) + 1

    # Ensure origin is visible
    min_x = min(min_x, -1)
    min_y = min(min_y, -1)
    max_x = max(max_x, 3)
    max_y = max(max_y, 3)

    # Scale to fit in viewBox
    width = 200
    height = 180
    margin = 30

    range_x = max_x - min_x
    range_y = max_y - min_y
    scale = min((width - 2*margin) / range_x, (height - 2*margin) / range_y)

    def to_svg_x(x):
        return margin + (x - min_x) * scale

    def to_svg_y(y):
        return height - margin - (y - min_y) * scale

    # Build SVG
    svg_parts = [
        f'<svg viewBox="0 0 {width} {height}" class="w-full max-w-[250px] h-auto" xmlns="http://www.w3.org/2000/svg">',
        '<defs>',
    ]

    # Add arrow markers
    for name, vec, color in vectors:
        svg_parts.append(create_arrow_marker(color, f'arrow_{name}'))

    svg_parts.append('</defs>')

    # Background
    svg_parts.append(f'<rect width="{width}" height="{height}" fill="{COLORS["bg"]}"/>')

    # Grid lines
    for x in range(int(min_x), int(max_x) + 1):
        sx = to_svg_x(x)
        svg_parts.append(f'<line x1="{sx}" y1="{margin-5}" x2="{sx}" y2="{height-margin+5}" stroke="{COLORS["grid"]}" stroke-width="0.5"/>')

    for y in range(int(min_y), int(max_y) + 1):
        sy = to_svg_y(y)
        svg_parts.append(f'<line x1="{margin-5}" y1="{sy}" x2="{width-margin+5}" y2="{sy}" stroke="{COLORS["grid"]}" stroke-width="0.5"/>')

    # Axes
    origin_x = to_svg_x(0)
    origin_y = to_svg_y(0)

    # X axis
    svg_parts.append(f'<line x1="{margin-10}" y1="{origin_y}" x2="{width-margin+15}" y2="{origin_y}" stroke="{COLORS["axis"]}" stroke-width="1.5"/>')
    svg_parts.append(f'<polygon points="{width-margin+15},{origin_y} {width-margin+5},{origin_y-4} {width-margin+5},{origin_y+4}" fill="{COLORS["axis"]}"/>')
    svg_parts.append(f'<text x="{width-margin+10}" y="{origin_y+15}" fill="{COLORS["label"]}" font-size="12" font-style="italic">x</text>')

    # Y axis
    svg_parts.append(f'<line x1="{origin_x}" y1="{height-margin+10}" x2="{origin_x}" y2="{margin-15}" stroke="{COLORS["axis"]}" stroke-width="1.5"/>')
    svg_parts.append(f'<polygon points="{origin_x},{margin-15} {origin_x-4},{margin-5} {origin_x+4},{margin-5}" fill="{COLORS["axis"]}"/>')
    svg_parts.append(f'<text x="{origin_x+8}" y="{margin-5}" fill="{COLORS["label"]}" font-size="12" font-style="italic">y</text>')

    # Origin label
    svg_parts.append(f'<text x="{origin_x-12}" y="{origin_y+15}" fill="{COLORS["label"]}" font-size="11">0</text>')

    # Unit marks
    if to_svg_x(1) - origin_x > 15:
        svg_parts.append(f'<text x="{to_svg_x(1)}" y="{origin_y+15}" fill="{COLORS["label"]}" font-size="11" text-anchor="middle">1</text>')
        svg_parts.append(f'<text x="{origin_x-10}" y="{to_svg_y(1)+4}" fill="{COLORS["label"]}" font-size="11" text-anchor="end">1</text>')

    # Draw vectors
    for name, vec, color in vectors:
        x1 = to_svg_x(vec['start'][0])
        y1 = to_svg_y(vec['start'][1])
        x2 = to_svg_x(vec['end'][0])
        y2 = to_svg_y(vec['end'][1])

        svg_parts.append(f'<line x1="{x1}" y1="{y1}" x2="{x2}" y2="{y2}" stroke="{color}" stroke-width="2.5" marker-end="url(#arrow_{name})"/>')

        # Vector label
        # Place label at end of vector, offset from the tip
        dx = vec['end'][0] - vec['start'][0]
        dy = vec['end'][1] - vec['start'][1]
        length = math.sqrt(dx*dx + dy*dy)
        if length > 0:
            # Normalize and offset
            nx = dx / length
            ny = dy / length
            label_x = x2 + nx * 12
            label_y = y2 - ny * 12
        else:
            label_x = x2 + 10
            label_y = y2 - 10

        svg_parts.append(f'<text x="{label_x}" y="{label_y}" fill="{color}" font-size="14" font-style="italic" font-weight="bold" text-anchor="middle" dominant-baseline="middle">{name}</text>')
        svg_parts.append(f'<text x="{label_x-0.5}" y="{label_y-8}" fill="{color}" font-size="9">→</text>')

    svg_parts.append('</svg>')

    return '\n'.join(svg_parts)


def generate_vectors_grid_svg(params):
    """Generate SVG for vectors with specific grid marks"""

    grid = params.get('grid', {'x': [0, 10], 'y': [0, 8]})

    vectors = []
    if 'a' in params:
        vectors.append(('a', params['a'], COLORS['vector_a']))
    if 'b' in params:
        vectors.append(('b', params['b'], COLORS['vector_b']))

    min_x, max_x = grid['x']
    min_y, max_y = grid['y']

    width = 220
    height = 180
    margin = 35

    range_x = max_x - min_x
    range_y = max_y - min_y
    scale = min((width - 2*margin) / range_x, (height - 2*margin) / range_y)

    def to_svg_x(x):
        return margin + (x - min_x) * scale

    def to_svg_y(y):
        return height - margin - (y - min_y) * scale

    svg_parts = [
        f'<svg viewBox="0 0 {width} {height}" class="w-full max-w-[250px] h-auto" xmlns="http://www.w3.org/2000/svg">',
        '<defs>',
    ]

    for name, vec, color in vectors:
        svg_parts.append(create_arrow_marker(color, f'arrow_{name}'))

    svg_parts.append('</defs>')
    svg_parts.append(f'<rect width="{width}" height="{height}" fill="{COLORS["bg"]}"/>')

    # Grid
    for x in range(int(min_x), int(max_x) + 1):
        sx = to_svg_x(x)
        svg_parts.append(f'<line x1="{sx}" y1="{margin-5}" x2="{sx}" y2="{height-margin+5}" stroke="{COLORS["grid"]}" stroke-width="0.5" stroke-dasharray="2,2"/>')

    for y in range(int(min_y), int(max_y) + 1):
        sy = to_svg_y(y)
        svg_parts.append(f'<line x1="{margin-5}" y1="{sy}" x2="{width-margin+5}" y2="{sy}" stroke="{COLORS["grid"]}" stroke-width="0.5" stroke-dasharray="2,2"/>')

    # Axes
    origin_x = to_svg_x(0)
    origin_y = to_svg_y(0)

    svg_parts.append(f'<line x1="{margin-10}" y1="{origin_y}" x2="{width-margin+15}" y2="{origin_y}" stroke="{COLORS["axis"]}" stroke-width="1.5"/>')
    svg_parts.append(f'<polygon points="{width-margin+15},{origin_y} {width-margin+5},{origin_y-4} {width-margin+5},{origin_y+4}" fill="{COLORS["axis"]}"/>')
    svg_parts.append(f'<text x="{width-margin+10}" y="{origin_y+15}" fill="{COLORS["label"]}" font-size="12" font-style="italic">x</text>')

    svg_parts.append(f'<line x1="{origin_x}" y1="{height-margin+10}" x2="{origin_x}" y2="{margin-15}" stroke="{COLORS["axis"]}" stroke-width="1.5"/>')
    svg_parts.append(f'<polygon points="{origin_x},{margin-15} {origin_x-4},{margin-5} {origin_x+4},{margin-5}" fill="{COLORS["axis"]}"/>')
    svg_parts.append(f'<text x="{origin_x+8}" y="{margin-5}" fill="{COLORS["label"]}" font-size="12" font-style="italic">y</text>')

    svg_parts.append(f'<text x="{origin_x-8}" y="{origin_y+12}" fill="{COLORS["label"]}" font-size="10">0</text>')

    # Specific grid marks
    for x in [3, 5, 8, 9, 10]:
        if min_x <= x <= max_x:
            sx = to_svg_x(x)
            svg_parts.append(f'<text x="{sx}" y="{origin_y+14}" fill="{COLORS["label"]}" font-size="10" text-anchor="middle">{x}</text>')

    for y in [2, 3, 4, 5, 7, 8, 9]:
        if min_y <= y <= max_y:
            sy = to_svg_y(y)
            svg_parts.append(f'<text x="{origin_x-8}" y="{sy+4}" fill="{COLORS["label"]}" font-size="10" text-anchor="end">{y}</text>')

    # Vectors
    for name, vec, color in vectors:
        x1 = to_svg_x(vec['start'][0])
        y1 = to_svg_y(vec['start'][1])
        x2 = to_svg_x(vec['end'][0])
        y2 = to_svg_y(vec['end'][1])

        svg_parts.append(f'<line x1="{x1}" y1="{y1}" x2="{x2}" y2="{y2}" stroke="{color}" stroke-width="2.5" marker-end="url(#arrow_{name})"/>')

        dx = vec['end'][0] - vec['start'][0]
        dy = vec['end'][1] - vec['start'][1]
        length = math.sqrt(dx*dx + dy*dy)
        if length > 0:
            nx = dx / length
            ny = dy / length
            label_x = x2 + nx * 12
            label_y = y2 - ny * 12
        else:
            label_x = x2 + 10
            label_y = y2 - 10

        svg_parts.append(f'<text x="{label_x}" y="{label_y}" fill="{color}" font-size="14" font-style="italic" font-weight="bold" text-anchor="middle" dominant-baseline="middle">{name}</text>')
        svg_parts.append(f'<text x="{label_x-0.5}" y="{label_y-8}" fill="{color}" font-size="9">→</text>')

    svg_parts.append('</svg>')
    return '\n'.join(svg_parts)


def process_topic_02():
    """Process topic_02.json and add SVG to tasks"""

    json_path = os.path.join(os.path.dirname(__file__), '..', 'storage', 'app', 'tasks', 'ege', 'topic_02.json')

    with open(json_path, 'r', encoding='utf-8') as f:
        data = json.load(f)

    svg_count = 0

    for block in data['blocks']:
        for zadanie in block['zadaniya']:
            for task in zadanie['tasks']:
                if 'svg_type' in task and 'params' in task:
                    svg_type = task['svg_type']
                    params = task['params']

                    if svg_type == 'vectors_2':
                        task['svg'] = generate_vectors_svg(params, 'vectors_2')
                        svg_count += 1
                    elif svg_type == 'vectors_3':
                        task['svg'] = generate_vectors_svg(params, 'vectors_3')
                        svg_count += 1
                    elif svg_type == 'vectors_grid':
                        task['svg'] = generate_vectors_grid_svg(params)
                        svg_count += 1

    with open(json_path, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=2)

    print(f"Generated {svg_count} SVG diagrams for topic 02")


if __name__ == '__main__':
    process_topic_02()
