<?php

// use Spatie\Csp\Directive;
// use Spatie\Csp\Keyword;

return [

    /*
     * Preset akan menentukan header CSP mana yang akan diatur. Preset CSP yang valid adalah
     * kelas apa pun yang mengimplementasikan `Spatie\Csp\Preset`
     */
    'presets' => [
        Spatie\Csp\Presets\Basic::class,
    ],

    /**
     * Daftarkan direktif CSP global tambahan di sini.
     */
    'directives' => [
        // [Directive::SCRIPT, [Keyword::UNSAFE_EVAL, Keyword::UNSAFE_INLINE]],
    ],

    /*
     * Preset ini akan dimasukkan dalam kebijakan report-only. Ini sangat bagus untuk menguji
     * kebijakan baru atau perubahan pada kebijakan CSP yang ada tanpa merusak apa pun.
     */
    'report_only_presets' => [
        //
    ],

    /**
     * Daftarkan direktif CSP global report-only tambahan di sini.
     */
    'report_only_directives' => [
        // [Directive::SCRIPT, [Keyword::UNSAFE_EVAL, Keyword::UNSAFE_INLINE]],
    ],

    /*
     * Semua pelanggaran terhadap kebijakan akan dilaporkan ke url ini.
     * Layanan hebat yang bisa Anda gunakan untuk ini adalah https://report-uri.com/
     */
    'report_uri' => env('CSP_REPORT_URI', ''),

    /*
     * Header akan ditambahkan hanya jika pengaturan ini disetel ke true.
     */
    'enabled' => env('CSP_ENABLED', true),

    /**
     * Header akan ditambahkan saat Vite melakukan hot reloading.
     */
    'enabled_while_hot_reloading' => env('CSP_ENABLED_WHILE_HOT_RELOADING', false),

    /*
     * Kelas yang bertanggung jawab untuk menghasilkan nonce yang digunakan dalam tag dan header sebaris.
     */
    'nonce_generator' => Spatie\Csp\Nonce\RandomString::class,

    /*
     * Setel false untuk menonaktifkan pembuatan dan penanganan nonce otomatis.
     * Ini berguna ketika Anda ingin menggunakan 'unsafe-inline' untuk script/style
     * dan tidak dapat menambahkan nonce sebaris.
     * Perhatikan bahwa ini akan membuat kebijakan CSP Anda kurang aman.
     */
    'nonce_enabled' => env('CSP_NONCE_ENABLED', true),
];
