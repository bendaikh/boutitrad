@props(['class' => 'h-5 w-8'])

<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 900 600" role="img" aria-label="Drapeau du Maroc" xmlns="http://www.w3.org/2000/svg">
    <rect width="900" height="600" fill="#C1272D"/>
    <g transform="translate(450 300)" fill="none" stroke="#006233" stroke-width="24" stroke-linejoin="round">
        <path d="M0,-140 L33,-43 L132,-43 L53,16 L86,113 L0,56 L-86,113 L-53,16 L-132,-43 L-33,-43 Z"/>
        <path d="M0,-90 L21,-28 L84,-28 L33,10 L54,72 L0,36 L-54,72 L-33,10 L-84,-28 L-21,-28 Z" fill="#006233" stroke="none"/>
    </g>
</svg>
