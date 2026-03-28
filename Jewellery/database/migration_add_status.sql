-- Migration: Thêm cột status vào bảng users
-- Chạy file này trên phpMyAdmin hoặc MySQL CLI

USE jewelry_db;

ALTER TABLE `users`
    ADD COLUMN `status` ENUM('Active', 'Locked') NOT NULL DEFAULT 'Active'
    AFTER `created_at`;
