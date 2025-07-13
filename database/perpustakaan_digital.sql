-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 13, 2025 at 09:48 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `perpustakaan_digital`
--

-- --------------------------------------------------------

--
-- Table structure for table `buku`
--

CREATE TABLE `buku` (
  `id_buku` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `kode_ddc` varchar(3) NOT NULL DEFAULT '000',
  `penulis` varchar(100) NOT NULL,
  `penerbit` varchar(100) DEFAULT NULL,
  `tahun_terbit` year(4) DEFAULT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buku`
--

INSERT INTO `buku` (`id_buku`, `judul`, `kode_ddc`, `penulis`, `penerbit`, `tahun_terbit`, `kategori`, `deskripsi`, `created_at`) VALUES
(5, 'laskar lalala', '800', 'joko', '2017', '2029', 'Pelajaran', '', '2025-05-25 12:17:10'),
(6, 'cripto', '600', 'timothi', '2025', '2027', 'Pelajaran', '', '2025-05-25 12:17:51'),
(999, 'Buku Test', '000', 'Penulis Test', NULL, NULL, NULL, NULL, '2025-05-25 15:45:15'),
(1000, 'cylion', '900', 'nito', '1999', '2013', 'Umum', '', '2025-05-26 03:36:45'),
(1001, 'buku pengetahuan', '000', 'ggg', '', '2022', 'Umum', '', '2025-06-21 07:05:56'),
(1002, 'Pengetahuan Angkasa', '300', 'Thomas', 'Neroc', '1999', NULL, '', '2025-07-09 08:30:41'),
(1003, 'Filosofi Teras', '100', 'Henry Manampiring', 'stoisme', '2021', NULL, '', '2025-07-09 08:32:35'),
(1004, 'The Passions of The Soul', '100', 'René Descartes', 'Perancis', '2012', NULL, '', '2025-07-09 08:33:47'),
(1005, 'A Basic Course in Anthropological Linguistics', '300', 'Danesi, Marsel', 'Canadian Scholars’ Press Inc', '2004', NULL, 'Language is a truly fascinating and enigmatic phenomenon. The scientific discipline that aims to study it, in all its dimensions, is known as linguistics. The particular approach that studies the relation between language, thought, and culture is known as anthropological linguistics (AL). Introducing the basics of AL, is the subject matter of this textbook. Known variously as ethnolinguistics, cultural linguistics, or linguistic anthropology, AL is a branch of both anthropology and linguistics. Traditionally, anthropological linguists have aimed to document and study the languages of indigenous cultures, especially North American ones', '2025-07-09 08:35:59'),
(1006, 'Best Practices in Hospitality and Tourism Marketing and Management: A Quality of Life Perspective', '300', 'Ana María Campon, José Antonio Folgado, José Manuel Hernández', 'Springer', '2019', NULL, 'This book series focuses on best practices in specialty areas of Quality of Life research, including among others potentially: community development, quality of work life, marketing, healthcare and public sector management.', '2025-07-09 08:38:20'),
(1007, 'BEREBUT KONTROL ATAS KESEJAHTERAAN: Kasus-Kasus Politisasi Demokrasi Di Tingkat Lokal', '300', 'Caroline Paskarina, Mariatul Asiah, Otto Gusti Madung', 'PolGov', '2016', NULL, 'Buku ini memang dilatarbelakangi oleh semangat kami selaku bagian dari demos yang ingin melihat, menjalankan dan menikmati manfaat demokrasi. Sebagai bagian dari generasi yang pernah merasakan hidup di bawah tekanan rezim otoritarian, kami sempat menyaksikan dan merasakan pahitnya hidup di bawah tekanan sosial dan politik dalam berbagai bentuk: pembatasan hak-hak sipil dan politik, perlakuan diskriminatif, kesewenang-wenangan kekuasaan, dan lain sebagainya.', '2025-07-09 08:39:17');

-- --------------------------------------------------------

--
-- Table structure for table `copy_buku`
--

CREATE TABLE `copy_buku` (
  `id_copy` int(11) NOT NULL,
  `id_buku` int(11) NOT NULL,
  `kode_unik` varchar(20) NOT NULL,
  `status` enum('Tersedia','Dipinjam','Hilang') DEFAULT 'Tersedia'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `copy_buku`
--

INSERT INTO `copy_buku` (`id_copy`, `id_buku`, `kode_unik`, `status`) VALUES
(7, 5, 'BUKU-005-1', 'Tersedia'),
(8, 5, 'BUKU-005-2', 'Tersedia'),
(9, 5, 'BUKU-005-3', 'Tersedia'),
(10, 5, 'BUKU-005-4', 'Tersedia'),
(11, 5, 'BUKU-005-5', 'Tersedia'),
(12, 6, 'BUKU-006-1', 'Tersedia'),
(13, 6, 'BUKU-006-2', 'Tersedia'),
(14, 6, 'BUKU-006-3', 'Tersedia'),
(15, 6, 'BUKU-006-4', 'Tersedia'),
(16, 999, 'BUKU-TEST-001', 'Dipinjam'),
(17, 1000, 'BUKU-1000-1', 'Tersedia'),
(18, 1000, 'BUKU-1000-2', 'Tersedia'),
(19, 1000, 'BUKU-1000-3', 'Tersedia'),
(20, 1000, 'BUKU-1000-4', 'Tersedia'),
(21, 1000, 'BUKU-1000-5', 'Tersedia'),
(22, 999, 'BUKU-TEST-2', 'Tersedia'),
(23, 999, 'BUKU-TEST-3', 'Tersedia'),
(24, 999, 'BUKU-TEST-4', 'Tersedia'),
(25, 1001, 'BUKU-1001-1', 'Tersedia'),
(26, 1001, 'BUKU-1001-2', 'Tersedia'),
(27, 1001, 'BUKU-1001-3', 'Tersedia'),
(28, 1001, 'BUKU-1001-4', 'Tersedia'),
(29, 999, 'BUKU-TEST-5', 'Tersedia'),
(30, 999, 'BUKU-TEST-6', 'Tersedia'),
(31, 1003, 'BUKU-1003-1', 'Tersedia'),
(32, 1004, 'BUKU-1004-1', 'Tersedia'),
(33, 1005, 'BUKU-1005-1', 'Tersedia'),
(34, 1002, 'BUKU-1002-1', 'Tersedia'),
(35, 1007, 'BUKU-1007-1', 'Tersedia'),
(36, 1006, 'BUKU-1006-1', 'Tersedia');

-- --------------------------------------------------------

--
-- Table structure for table `log_whatsapp`
--

CREATE TABLE `log_whatsapp` (
  `id_log` int(11) NOT NULL,
  `id_peminjaman` int(11) NOT NULL,
  `tgl_kirim` datetime NOT NULL,
  `status` enum('terkirim','gagal') NOT NULL,
  `pesan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `log_whatsapp`
--

INSERT INTO `log_whatsapp` (`id_log`, `id_peminjaman`, `tgl_kirim`, `status`, `pesan`) VALUES
(1, 18, '2025-06-20 19:44:35', 'gagal', 'device not connected or not found'),
(2, 19, '2025-06-20 19:44:36', 'gagal', 'device not connected or not found'),
(3, 19, '2025-06-20 20:40:50', 'gagal', 'device not connected or not found'),
(4, 19, '2025-06-20 20:40:57', 'gagal', 'device not connected or not found'),
(5, 19, '2025-06-20 20:41:12', 'gagal', 'device not connected or not found'),
(6, 19, '2025-07-05 13:05:15', 'terkirim', 'Notifikasi terkirim');

-- --------------------------------------------------------

--
-- Table structure for table `peminjaman`
--

CREATE TABLE `peminjaman` (
  `id_peminjaman` int(11) NOT NULL,
  `id_siswa` int(11) NOT NULL,
  `id_copy` int(11) NOT NULL,
  `tgl_pinjam` date NOT NULL,
  `tgl_batas_kembali` date NOT NULL,
  `tgl_kembali` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `peminjaman`
--

INSERT INTO `peminjaman` (`id_peminjaman`, `id_siswa`, `id_copy`, `tgl_pinjam`, `tgl_batas_kembali`, `tgl_kembali`) VALUES
(1, 2, 12, '2025-05-25', '2025-05-28', '2025-05-25'),
(2, 1, 16, '2025-05-25', '2025-05-28', '2025-05-25'),
(3, 1, 16, '2025-05-25', '2025-05-28', '2025-05-25'),
(4, 1, 16, '2025-05-25', '2025-05-28', '2025-05-25'),
(5, 1, 16, '2025-05-25', '2025-05-28', '2025-05-25'),
(6, 1, 16, '2025-05-25', '2025-05-28', '2025-05-25'),
(7, 1, 16, '2025-05-25', '2025-05-28', '2025-05-25'),
(8, 1, 16, '2025-05-25', '2025-05-28', '2025-05-25'),
(9, 1, 16, '2025-04-25', '2025-04-28', '2025-05-25'),
(10, 1, 16, '2025-05-25', '2025-05-28', '2025-05-25'),
(11, 1, 16, '2025-05-25', '2025-05-28', '2025-05-25'),
(12, 1, 16, '2025-05-25', '2025-05-28', '2025-05-25'),
(13, 1, 16, '2025-06-25', '2025-06-28', '2025-05-25'),
(14, 1, 16, '2025-05-25', '2025-05-28', '2025-05-25'),
(15, 1, 16, '2025-05-18', '2025-05-21', '2025-05-25'),
(16, 1, 16, '2025-05-25', '2025-05-28', '2025-06-13'),
(17, 1, 16, '2025-05-25', '2025-05-28', '2025-06-07'),
(18, 2, 12, '2025-05-26', '2025-05-29', '2025-07-07'),
(19, 3, 17, '2025-05-26', '2025-05-29', '2025-07-07'),
(20, 2, 16, '2025-07-08', '2025-07-11', '2025-07-08'),
(21, 2, 16, '2025-07-08', '2025-07-11', NULL),
(22, 5, 35, '2025-07-09', '2025-07-14', '2025-07-09');

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `id_siswa` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `kelas` varchar(10) NOT NULL,
  `no_telepon` varchar(15) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`id_siswa`, `nama`, `kelas`, `no_telepon`, `created_at`) VALUES
(1, 'Siswa Test', '12-TEST', '', '2025-05-25 15:44:53'),
(2, 'kevin', '12-A', '62890788755666', '2025-05-25 11:44:51'),
(3, 'bifo ciko', '9-A', '85781791560', '2025-05-26 03:37:19'),
(4, 'ashabil', '9 A', '875767657476', '2025-06-21 16:25:28'),
(5, 'rizky ahmat hidayah', '11 A', '62878979844344', '2025-07-09 07:23:05');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id_staff` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id_staff`, `username`, `password`, `nama`) VALUES
(2, 'admin', '$2a$12$2vDx451yvSl5pgALPUil5OXlcXJhp9nbwjdjZOdsf4olAAx9U/dOa', 'administrator');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `buku`
--
ALTER TABLE `buku`
  ADD PRIMARY KEY (`id_buku`);

--
-- Indexes for table `copy_buku`
--
ALTER TABLE `copy_buku`
  ADD PRIMARY KEY (`id_copy`),
  ADD UNIQUE KEY `kode_unik` (`kode_unik`),
  ADD KEY `id_buku` (`id_buku`);

--
-- Indexes for table `log_whatsapp`
--
ALTER TABLE `log_whatsapp`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `id_peminjaman` (`id_peminjaman`);

--
-- Indexes for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD PRIMARY KEY (`id_peminjaman`),
  ADD KEY `id_siswa` (`id_siswa`),
  ADD KEY `id_copy` (`id_copy`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id_siswa`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id_staff`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `buku`
--
ALTER TABLE `buku`
  MODIFY `id_buku` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1008;

--
-- AUTO_INCREMENT for table `copy_buku`
--
ALTER TABLE `copy_buku`
  MODIFY `id_copy` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `log_whatsapp`
--
ALTER TABLE `log_whatsapp`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `peminjaman`
--
ALTER TABLE `peminjaman`
  MODIFY `id_peminjaman` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id_siswa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id_staff` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `copy_buku`
--
ALTER TABLE `copy_buku`
  ADD CONSTRAINT `copy_buku_ibfk_1` FOREIGN KEY (`id_buku`) REFERENCES `buku` (`id_buku`) ON DELETE CASCADE;

--
-- Constraints for table `log_whatsapp`
--
ALTER TABLE `log_whatsapp`
  ADD CONSTRAINT `log_whatsapp_ibfk_1` FOREIGN KEY (`id_peminjaman`) REFERENCES `peminjaman` (`id_peminjaman`) ON DELETE CASCADE;

--
-- Constraints for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD CONSTRAINT `peminjaman_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE,
  ADD CONSTRAINT `peminjaman_ibfk_2` FOREIGN KEY (`id_copy`) REFERENCES `copy_buku` (`id_copy`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
