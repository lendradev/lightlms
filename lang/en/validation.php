<?php

/*
 * The validation messages Laravel ships are English. This file only overrides
 * the ones that read badly in the interface; everything else falls back to the
 * framework's own file.
 */
return [
    'required' => 'The :attribute field is required.',
    'email' => 'The :attribute must be a valid email address.',
    'unique' => 'The :attribute has already been taken.',
    'confirmed' => 'The :attribute confirmation does not match.',
    'accepted' => 'The :attribute must be accepted.',
    'max' => [
        'string' => 'The :attribute must not be longer than :max characters.',
        'numeric' => 'The :attribute must not be greater than :max.',
    ],
    'min' => [
        'string' => 'The :attribute must be at least :min characters.',
        'numeric' => 'The :attribute must be at least :min.',
    ],
    'numeric' => 'The :attribute must be a number.',
    'integer' => 'The :attribute must be an integer.',
    'exists' => 'The selected :attribute is invalid.',
    'in' => 'The selected :attribute is invalid.',
    'date' => 'The :attribute is not a valid date.',
    'url' => 'The :attribute must be a valid URL.',
    'mimes' => 'The :attribute must be a file of type: :values.',
    'max.file' => 'The :attribute must not be greater than :max kilobytes.',
];
