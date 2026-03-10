-- ============================================
-- Blood Requisition Management System
-- Database Schema for Badhan DU Zone
-- ============================================

CREATE DATABASE IF NOT EXISTS `badhan_duzone` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `badhan_duzone`;

-- ============================================
-- Users Table
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'member') NOT NULL DEFAULT 'member',
    `name` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- Requisitions Table
-- ============================================
CREATE TABLE IF NOT EXISTS `requisitions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_name` VARCHAR(150) NOT NULL,
    `patient_age` INT NOT NULL,
    `blood_group` ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `component` ENUM('Whole Blood','RCC/PCV/PRBC','Platelet','FFP','Cryoprecipitate') NOT NULL,
    `hospital_name` VARCHAR(200) NOT NULL,
    `problem` TEXT NOT NULL,
    `attendant_name` VARCHAR(150) NOT NULL,
    `attendant_blood_group` ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-','Don\'t Know') NOT NULL,
    `attendant_address` VARCHAR(255) NOT NULL DEFAULT '',
    `attendant_contact` VARCHAR(20) NOT NULL,
    `comment` ENUM('Managed','Referred','Others','') DEFAULT '',
    `managed_by` VARCHAR(150) DEFAULT '',
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Zone Contact Information Table
-- ============================================
CREATE TABLE IF NOT EXISTS `zone_contacts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `role` VARCHAR(100) NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `contact` VARCHAR(20) NOT NULL
) ENGINE=InnoDB;

-- ============================================
-- Seed Data
-- ============================================

-- Admin (username: admin)
INSERT INTO `users` (`username`, `password`, `role`, `name`) VALUES
('admin', '$2y$10$v304qUd.qSKy7WF.K8qbK.8NrN8rqIPIHbV2DHIDaOlkQ43ynehX6', 'admin', 'System Admin');

-- Member (username: member)
INSERT INTO `users` (`username`, `password`, `role`, `name`) VALUES
('member', '$2y$10$7s8/AoTisEmNhW4miiFfJuEO144t8KxdZsq228FkVG3hGJNYvODUG', 'member', 'Member');

-- Zone Contact Information
INSERT INTO `zone_contacts` (`role`, `name`, `contact`) VALUES
('President', 'President', '01842820792'),
('Secretary', 'Secretary', '01308001718'),
('BTC', 'BTC', '01575024626'),
('Quantum', 'Quantum', '01714010869');