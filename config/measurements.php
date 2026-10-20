<?php

/*
 * All measurements are in inches. Each field: label, min, max.
 * Garment keys match product_type values. 'other' has no profile and
 * accepts size-quantity entry only.
 */
return [
    'unit' => 'in',
    'max_people_per_item' => 500,
    'max_decimals' => 2,

    'sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL'],

    'profiles' => [
        'shirt' => [
            'neck' => ['label' => 'Neck', 'min' => 8, 'max' => 30],
            'chest' => ['label' => 'Chest', 'min' => 20, 'max' => 70],
            'waist' => ['label' => 'Waist', 'min' => 18, 'max' => 70],
            'shoulder' => ['label' => 'Shoulder width', 'min' => 8, 'max' => 30],
            'sleeve' => ['label' => 'Sleeve length', 'min' => 5, 'max' => 40],
            'length' => ['label' => 'Shirt length', 'min' => 15, 'max' => 45],
        ],
        'trouser' => [
            'waist' => ['label' => 'Waist', 'min' => 18, 'max' => 70],
            'hip' => ['label' => 'Hip', 'min' => 20, 'max' => 75],
            'inseam' => ['label' => 'Inseam', 'min' => 10, 'max' => 40],
            'outseam' => ['label' => 'Outseam', 'min' => 15, 'max' => 55],
            'thigh' => ['label' => 'Thigh', 'min' => 10, 'max' => 40],
            'bottom' => ['label' => 'Bottom opening', 'min' => 6, 'max' => 30],
        ],
        'shorts' => [
            'waist' => ['label' => 'Waist', 'min' => 18, 'max' => 70],
            'hip' => ['label' => 'Hip', 'min' => 20, 'max' => 75],
            'inseam' => ['label' => 'Inseam', 'min' => 2, 'max' => 20],
            'outseam' => ['label' => 'Outseam', 'min' => 8, 'max' => 35],
            'thigh' => ['label' => 'Thigh', 'min' => 10, 'max' => 40],
        ],
        'pinafore' => [
            'bust' => ['label' => 'Bust', 'min' => 20, 'max' => 70],
            'waist' => ['label' => 'Waist', 'min' => 18, 'max' => 70],
            'hip' => ['label' => 'Hip', 'min' => 20, 'max' => 75],
            'shoulder' => ['label' => 'Shoulder width', 'min' => 8, 'max' => 30],
            'length' => ['label' => 'Length', 'min' => 15, 'max' => 60],
        ],
        'overall' => [
            'chest' => ['label' => 'Chest', 'min' => 20, 'max' => 70],
            'waist' => ['label' => 'Waist', 'min' => 18, 'max' => 70],
            'hip' => ['label' => 'Hip', 'min' => 20, 'max' => 75],
            'inseam' => ['label' => 'Inseam', 'min' => 10, 'max' => 40],
            'torso' => ['label' => 'Torso length', 'min' => 15, 'max' => 45],
        ],
        'jumpsuit' => [
            'chest' => ['label' => 'Chest', 'min' => 20, 'max' => 70],
            'waist' => ['label' => 'Waist', 'min' => 18, 'max' => 70],
            'hip' => ['label' => 'Hip', 'min' => 20, 'max' => 75],
            'inseam' => ['label' => 'Inseam', 'min' => 10, 'max' => 40],
            'torso' => ['label' => 'Torso length', 'min' => 15, 'max' => 45],
        ],
    ],
];
