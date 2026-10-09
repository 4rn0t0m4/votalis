@props(['name', 'label' => null])
{{-- Illustrations abstraites (formes géométriques, aucune figure ni symbole) dessinées en SVG, quelques Ko chacune. --}}
@include('components.illustrations.'.$name, ['attributes' => $attributes, 'label' => $label])
