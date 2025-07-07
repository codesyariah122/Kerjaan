<?php
/*
Plugin Name: Tracer Study Kampus Pro | Pronya Pro Kontra lagi
Description: Plugin Tracer Study dengan login alumni dan statistik.
Version: 1.0
Author: Puji Ermanto<pujiermanto@gmail.com> | AKA Maman Oke | AKA Dadang Sumadi
*/

if (!defined('ABSPATH')) exit;

// 🔧 Include semua file
include_once plugin_dir_path(__FILE__) . 'includes/login-form.php';
include_once plugin_dir_path(__FILE__) . 'includes/tracer-form.php';
include_once plugin_dir_path(__FILE__) . 'includes/admin-menu.php';
include_once plugin_dir_path(__FILE__) . 'includes/import-alumni.php';
include_once plugin_dir_path(__FILE__) . 'includes/charts.php';
include_once plugin_dir_path(__FILE__) . 'includes/questions.php';
include_once plugin_dir_path(__FILE__) . 'includes/alumni.php';
include_once plugin_dir_path(__FILE__) . 'includes/logout.php';

// 🔐 Aktifkan plugin → buat tabel
register_activation_hook(__FILE__, function () {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();

    // Table alumni login
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tracer_alumni_login (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        nim VARCHAR(50) UNIQUE,
        nama VARCHAR(255),
        tanggal_lahir DATE
    ) $charset;");

    // Table hasil tracer
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tracer_alumni (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        nim VARCHAR(50),
        prodi VARCHAR(100),
        tahun_lulus VARCHAR(10),
        status_pekerjaan VARCHAR(100),
        gaji VARCHAR(100),
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) $charset;");

    // Tabel pertanyaan dinamis
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tracer_questions (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        pertanyaan TEXT,
        tipe_input VARCHAR(20),
        opsi TEXT NULL,
        required TINYINT(1),
        urutan INT
    ) $charset;");

    // Tabel jawaban JSON
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tracer_responses (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        nim VARCHAR(50),
        jawaban LONGTEXT,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) $charset;");
});

// 🔐 Start session untuk login alumni
add_action('init', function () {
    if (!session_id()) {
        session_start();
    }
});
