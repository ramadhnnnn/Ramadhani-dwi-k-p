-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jul 19, 2025 at 02:57 PM
-- Server version: 10.11.10-MariaDB
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u524099158_puzzle`
--

-- --------------------------------------------------------

--
-- Table structure for table `achievements`
--

CREATE TABLE `achievements` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `achievement_type` enum('FIRST_WIN','SPEED_DEMON','PERFECT_SOLVER','DEDICATED_PLAYER','STREAK_MASTER','PRO_PLAYER') NOT NULL,
  `achieved_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `progress` int(11) DEFAULT 0,
  `target` int(11) DEFAULT 1,
  `update_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `challenge_rewards`
--

CREATE TABLE `challenge_rewards` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `challenge_id` int(11) NOT NULL,
  `xp_earned` int(11) NOT NULL,
  `rewarded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `progress_at_claim` int(11) DEFAULT 0,
  `daily_count_at_claim` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `challenge_rewards`
--

INSERT INTO `challenge_rewards` (`id`, `user_id`, `challenge_id`, `xp_earned`, `rewarded_at`, `progress_at_claim`, `daily_count_at_claim`) VALUES
(53, 81, 13, 750, '2025-03-11 01:03:48', 0, 0),
(54, 81, 16, 900, '2025-03-11 16:42:29', 0, 0),
(55, 85, 13, 750, '2025-03-14 17:15:08', 0, 0),
(56, 85, 16, 900, '2025-03-14 17:15:08', 0, 0),
(57, 87, 13, 750, '2025-03-15 06:11:16', 0, 0),
(58, 87, 16, 900, '2025-03-15 06:14:24', 0, 0),
(59, 88, 13, 750, '2025-03-15 15:36:40', 0, 0),
(60, 88, 16, 900, '2025-03-15 15:37:20', 0, 0),
(61, 90, 13, 750, '2025-03-16 00:01:58', 0, 0),
(62, 90, 16, 900, '2025-03-16 00:03:00', 0, 0),
(63, 90, 17, 1200, '2025-03-16 00:07:06', 0, 0),
(64, 89, 13, 750, '2025-03-16 06:26:34', 0, 0),
(65, 89, 16, 900, '2025-03-16 06:26:47', 0, 0),
(66, 89, 17, 1200, '2025-03-16 06:34:28', 0, 0),
(67, 91, 13, 750, '2025-03-16 11:08:19', 0, 0),
(68, 91, 16, 900, '2025-03-16 11:09:31', 0, 0),
(69, 91, 17, 1200, '2025-03-16 11:15:52', 0, 0),
(70, 92, 13, 750, '2025-03-16 11:47:40', 0, 0),
(71, 92, 16, 900, '2025-03-16 11:53:03', 0, 0),
(72, 92, 15, 800, '2025-03-16 11:57:12', 0, 0),
(73, 92, 17, 1200, '2025-03-16 12:10:14', 0, 0),
(74, 87, 17, 1200, '2025-03-17 04:39:19', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `puzzle_scores`
--

CREATE TABLE `puzzle_scores` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `difficulty` varchar(50) NOT NULL DEFAULT 'medium',
  `steps` int(11) NOT NULL,
  `completion_time` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed` tinyint(1) DEFAULT 0,
  `xp_earned` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `puzzle_scores`
--

INSERT INTO `puzzle_scores` (`id`, `user_id`, `difficulty`, `steps`, `completion_time`, `created_at`, `completed`, `xp_earned`) VALUES
(363, 81, 'easy', 6, 7, '2025-03-10 13:01:37', 0, 335),
(364, 81, 'easy', 9, 25, '2025-03-10 13:15:24', 0, 320),
(365, 81, 'easy', 7, 10, '2025-03-11 01:03:48', 0, 331),
(366, 81, 'easy', 8, 10, '2025-03-11 16:32:43', 0, 329),
(367, 81, 'easy', 7, 11, '2025-03-11 16:42:29', 0, 331),
(368, 81, 'easy', 11, 23, '2025-03-12 06:12:48', 0, 317),
(369, 81, 'easy', 31, 49, '2025-03-12 12:00:35', 0, 264),
(370, 81, 'easy', 6, 8, '2025-03-12 15:52:14', 0, 334),
(371, 84, 'easy', 22, 33, '2025-03-13 13:32:49', 0, 290),
(372, 84, 'easy', 11, 30, '2025-03-13 13:33:34', 0, 313),
(373, 84, 'hard', 44, 116, '2025-03-13 13:35:49', 0, 304),
(374, 84, 'easy', 14, 21, '2025-03-13 13:37:02', 0, 312),
(375, 84, 'easy', 9, 20, '2025-03-13 13:37:41', 0, 322),
(376, 85, 'easy', 16, 25, '2025-03-14 10:09:40', 0, 306),
(377, 85, 'easy', 15, 20, '2025-03-14 10:10:15', 0, 310),
(378, 85, 'medium', 21, 25, '2025-03-14 10:10:53', 0, 346),
(379, 85, 'easy', 4, 7, '2025-03-14 10:11:16', 0, 339),
(380, 85, 'easy', 11, 13, '2025-03-14 10:11:45', 0, 322),
(381, 85, 'easy', 7, 8, '2025-03-14 10:12:10', 0, 332),
(382, 85, 'easy', 8, 11, '2025-03-14 17:15:08', 0, 329),
(383, 85, 'easy', 6, 10, '2025-03-14 17:15:23', 0, 333),
(384, 85, 'easy', 14, 19, '2025-03-14 17:15:45', 0, 313),
(385, 86, 'easy', 20, 19, '2025-03-15 05:16:11', 0, 301),
(386, 86, 'easy', 70, 55, '2025-03-15 05:17:10', 0, 223),
(387, 86, 'easy', 16, 24, '2025-03-15 05:17:45', 0, 306),
(388, 86, 'easy', 10, 13, '2025-03-15 05:18:00', 0, 324),
(389, 86, 'easy', 8, 12, '2025-03-15 05:18:15', 0, 328),
(390, 86, 'easy', 18, 22, '2025-03-15 05:18:38', 0, 303),
(391, 86, 'easy', 21, 24, '2025-03-15 05:19:04', 0, 296),
(392, 86, 'easy', 12, 13, '2025-03-15 05:19:19', 0, 320),
(393, 86, 'hard', 48, 125, '2025-03-15 05:23:26', 0, 292),
(394, 87, 'easy', 9, 22, '2025-03-15 06:10:06', 0, 321),
(395, 87, 'easy', 6, 32, '2025-03-15 06:10:44', 0, 322),
(396, 87, 'easy', 8, 23, '2025-03-15 06:11:16', 0, 323),
(397, 87, 'medium', 20, 52, '2025-03-15 06:12:22', 0, 334),
(398, 87, 'easy', 6, 22, '2025-03-15 06:14:10', 0, 327),
(399, 87, 'easy', 8, 11, '2025-03-15 06:14:24', 0, 329),
(400, 87, 'easy', 7, 9, '2025-03-15 06:14:36', 0, 332),
(401, 87, 'easy', 5, 9, '2025-03-15 06:14:50', 0, 336),
(402, 87, 'easy', 13, 27, '2025-03-15 06:15:22', 0, 311),
(403, 87, 'easy', 6, 9, '2025-03-15 06:15:39', 0, 334),
(404, 87, 'easy', 8, 24, '2025-03-15 06:16:06', 0, 322),
(405, 87, 'easy', 8, 12, '2025-03-15 06:16:21', 0, 328),
(406, 87, 'easy', 10, 13, '2025-03-15 06:16:37', 0, 324),
(407, 87, 'easy', 12, 19, '2025-03-15 06:16:58', 0, 317),
(408, 87, 'easy', 9, 14, '2025-03-15 06:17:15', 0, 325),
(409, 87, 'easy', 12, 18, '2025-03-15 06:17:35', 0, 317),
(410, 87, 'easy', 6, 9, '2025-03-15 06:18:11', 0, 334),
(411, 88, 'easy', 15, 28, '2025-03-15 15:31:03', 0, 306),
(412, 88, 'easy', 9, 14, '2025-03-15 15:32:13', 0, 325),
(413, 88, 'easy', 10, 13, '2025-03-15 15:33:01', 0, 324),
(414, 88, 'easy', 10, 18, '2025-03-15 15:36:40', 0, 321),
(415, 88, 'easy', 6, 9, '2025-03-15 15:37:20', 0, 334),
(416, 88, 'easy', 12, 16, '2025-03-15 15:37:39', 0, 318),
(417, 88, 'easy', 11, 18, '2025-03-15 15:37:59', 0, 319),
(418, 88, 'easy', 6, 9, '2025-03-15 15:38:11', 0, 334),
(419, 88, 'easy', 6, 7, '2025-03-15 15:38:19', 0, 335),
(420, 88, 'easy', 4, 5, '2025-03-15 15:38:26', 0, 340),
(421, 88, 'easy', 10, 17, '2025-03-15 15:38:46', 0, 322),
(422, 88, 'easy', 6, 5, '2025-03-15 15:38:53', 0, 336),
(423, 88, 'easy', 4, 5, '2025-03-15 15:39:07', 0, 340),
(424, 88, 'easy', 8, 7, '2025-03-15 15:39:17', 0, 331),
(425, 88, 'easy', 10, 11, '2025-03-15 15:39:37', 0, 325),
(426, 88, 'easy', 6, 7, '2025-03-15 15:39:47', 0, 335),
(427, 88, 'easy', 4, 3, '2025-03-15 15:40:19', 0, 341),
(428, 90, 'easy', 4, 18, '2025-03-16 00:00:16', 0, 333),
(429, 90, 'medium', 17, 47, '2025-03-16 00:01:08', 0, 343),
(430, 90, 'easy', 8, 20, '2025-03-16 00:01:49', 0, 324),
(431, 90, 'easy', 4, 6, '2025-03-16 00:01:58', 0, 339),
(432, 90, 'easy', 6, 9, '2025-03-16 00:02:10', 0, 334),
(433, 90, 'easy', 10, 10, '2025-03-16 00:03:00', 0, 325),
(434, 90, 'easy', 6, 6, '2025-03-16 00:03:08', 0, 335),
(435, 90, 'easy', 12, 18, '2025-03-16 00:03:46', 0, 317),
(436, 90, 'easy', 16, 14, '2025-03-16 00:04:02', 0, 311),
(437, 90, 'easy', 11, 12, '2025-03-16 00:04:16', 0, 322),
(438, 90, 'easy', 15, 15, '2025-03-16 00:04:33', 0, 313),
(439, 90, 'easy', 6, 11, '2025-03-16 00:04:45', 0, 333),
(440, 90, 'easy', 6, 7, '2025-03-16 00:05:00', 0, 335),
(441, 90, 'easy', 6, 5, '2025-03-16 00:05:07', 0, 336),
(442, 90, 'easy', 6, 6, '2025-03-16 00:05:39', 0, 335),
(443, 90, 'easy', 10, 9, '2025-03-16 00:05:59', 0, 326),
(444, 90, 'easy', 14, 14, '2025-03-16 00:06:19', 0, 315),
(445, 90, 'easy', 11, 14, '2025-03-16 00:06:35', 0, 321),
(446, 90, 'easy', 11, 16, '2025-03-16 00:06:53', 0, 320),
(447, 90, 'easy', 9, 11, '2025-03-16 00:07:06', 0, 327),
(448, 90, 'easy', 6, 6, '2025-03-16 00:07:14', 0, 335),
(449, 90, 'easy', 8, 10, '2025-03-16 00:07:25', 0, 329),
(450, 90, 'easy', 6, 6, '2025-03-16 00:07:33', 0, 335),
(451, 90, 'easy', 11, 10, '2025-03-16 00:07:45', 0, 323),
(452, 90, 'medium', 18, 25, '2025-03-16 00:09:17', 0, 352),
(453, 90, 'easy', 7, 7, '2025-03-16 00:09:26', 0, 333),
(454, 89, 'medium', 54, 100, '2025-03-16 06:23:39', 0, 250),
(455, 89, 'easy', 7, 13, '2025-03-16 06:25:07', 0, 330),
(456, 89, 'easy', 9, 13, '2025-03-16 06:25:36', 0, 326),
(457, 89, 'easy', 11, 19, '2025-03-16 06:25:58', 0, 319),
(458, 89, 'easy', 10, 17, '2025-03-16 06:26:34', 0, 322),
(459, 89, 'easy', 7, 10, '2025-03-16 06:26:47', 0, 331),
(460, 89, 'easy', 8, 14, '2025-03-16 06:27:03', 0, 327),
(461, 89, 'easy', 18, 24, '2025-03-16 06:27:30', 0, 302),
(462, 89, 'easy', 4, 7, '2025-03-16 06:27:40', 0, 339),
(463, 89, 'easy', 6, 10, '2025-03-16 06:27:52', 0, 333),
(464, 89, 'easy', 6, 10, '2025-03-16 06:28:03', 0, 333),
(465, 89, 'easy', 22, 38, '2025-03-16 06:28:43', 0, 287),
(466, 89, 'easy', 10, 19, '2025-03-16 06:29:27', 0, 321),
(467, 89, 'easy', 7, 10, '2025-03-16 06:29:45', 0, 331),
(468, 89, 'easy', 16, 41, '2025-03-16 06:32:06', 0, 298),
(469, 89, 'easy', 11, 21, '2025-03-16 06:32:48', 0, 318),
(470, 89, 'easy', 7, 11, '2025-03-16 06:33:04', 0, 331),
(471, 89, 'easy', 4, 5, '2025-03-16 06:33:15', 0, 340),
(472, 89, 'easy', 15, 21, '2025-03-16 06:33:38', 0, 310),
(473, 89, 'easy', 9, 12, '2025-03-16 06:33:54', 0, 326),
(474, 89, 'easy', 6, 10, '2025-03-16 06:34:06', 0, 333),
(475, 89, 'easy', 16, 19, '2025-03-16 06:34:28', 0, 309),
(476, 91, 'easy', 4, 17, '2025-03-16 11:06:35', 0, 334),
(477, 91, 'easy', 7, 16, '2025-03-16 11:06:56', 0, 328),
(478, 91, 'easy', 8, 12, '2025-03-16 11:08:19', 0, 328),
(479, 91, 'easy', 9, 14, '2025-03-16 11:08:40', 0, 325),
(480, 91, 'easy', 11, 20, '2025-03-16 11:09:31', 0, 318),
(481, 91, 'easy', 6, 8, '2025-03-16 11:09:47', 0, 334),
(482, 91, 'easy', 10, 12, '2025-03-16 11:10:02', 0, 324),
(483, 91, 'easy', 6, 10, '2025-03-16 11:11:01', 0, 333),
(484, 91, 'easy', 10, 15, '2025-03-16 11:11:18', 0, 323),
(485, 91, 'easy', 8, 11, '2025-03-16 11:11:33', 0, 329),
(486, 91, 'hard', 58, 98, '2025-03-16 11:13:13', 0, 301),
(487, 91, 'easy', 9, 11, '2025-03-16 11:14:01', 0, 327),
(488, 91, 'easy', 4, 4, '2025-03-16 11:14:08', 0, 340),
(489, 91, 'easy', 7, 12, '2025-03-16 11:14:24', 0, 330),
(490, 91, 'easy', 4, 4, '2025-03-16 11:14:33', 0, 340),
(491, 91, 'easy', 8, 5, '2025-03-16 11:14:42', 0, 332),
(492, 91, 'easy', 10, 11, '2025-03-16 11:14:56', 0, 325),
(493, 91, 'easy', 8, 12, '2025-03-16 11:15:11', 0, 328),
(494, 91, 'easy', 11, 13, '2025-03-16 11:15:28', 0, 322),
(495, 91, 'easy', 6, 11, '2025-03-16 11:15:41', 0, 333),
(496, 91, 'easy', 6, 8, '2025-03-16 11:15:52', 0, 334),
(497, 92, 'easy', 17, 35, '2025-03-16 11:43:25', 0, 299),
(498, 92, 'easy', 21, 23, '2025-03-16 11:43:57', 0, 297),
(499, 92, 'easy', 23, 34, '2025-03-16 11:45:12', 0, 287),
(500, 92, 'easy', 7, 26, '2025-03-16 11:45:47', 0, 323),
(501, 92, 'easy', 5, 12, '2025-03-16 11:46:37', 0, 334),
(502, 92, 'easy', 8, 15, '2025-03-16 11:47:40', 0, 327),
(503, 92, 'easy', 4, 9, '2025-03-16 11:47:53', 0, 338),
(504, 92, 'hard', 48, 171, '2025-03-16 11:51:57', 0, 269),
(505, 92, 'easy', 12, 28, '2025-03-16 11:53:03', 0, 312),
(506, 92, 'hard', 35, 113, '2025-03-16 11:55:22', 0, 324),
(507, 92, 'hard', 38, 92, '2025-03-16 11:57:12', 0, 328),
(508, 92, 'easy', 9, 19, '2025-03-16 11:59:04', 0, 323),
(509, 92, 'easy', 6, 15, '2025-03-16 11:59:23', 0, 331),
(510, 92, 'easy', 9, 14, '2025-03-16 11:59:42', 0, 325),
(511, 92, 'easy', 5, 22, '2025-03-16 12:05:23', 0, 329),
(512, 92, 'easy', 10, 20, '2025-03-16 12:05:47', 0, 320),
(513, 92, 'easy', 12, 21, '2025-03-16 12:06:23', 0, 316),
(514, 92, 'easy', 8, 14, '2025-03-16 12:06:40', 0, 327),
(515, 92, 'easy', 9, 22, '2025-03-16 12:07:06', 0, 321),
(516, 92, 'easy', 6, 9, '2025-03-16 12:08:00', 0, 334),
(517, 92, 'easy', 10, 36, '2025-03-16 12:08:39', 0, 312),
(518, 92, 'easy', 8, 11, '2025-03-16 12:09:06', 0, 329),
(519, 92, 'easy', 8, 17, '2025-03-16 12:09:33', 0, 326),
(520, 92, 'easy', 8, 19, '2025-03-16 12:09:55', 0, 325),
(521, 92, 'easy', 9, 17, '2025-03-16 12:10:14', 0, 324),
(522, 92, 'easy', 6, 8, '2025-03-16 12:10:25', 0, 334),
(523, 92, 'easy', 4, 7, '2025-03-16 12:10:34', 0, 339),
(524, 92, 'easy', 10, 15, '2025-03-16 12:10:57', 0, 323),
(525, 92, 'easy', 6, 16, '2025-03-16 12:11:16', 0, 330),
(526, 87, 'easy', 10, 21, '2025-03-17 04:38:41', 0, 320),
(527, 87, 'easy', 8, 17, '2025-03-17 04:39:00', 0, 326),
(528, 87, 'easy', 8, 13, '2025-03-17 04:39:19', 0, 328),
(529, 93, 'easy', 13, 39, '2025-03-18 02:42:55', 0, 305),
(530, 93, 'easy', 11, 26, '2025-03-18 02:43:25', 0, 315),
(531, 93, 'easy', 8, 20, '2025-03-18 02:43:49', 0, 324),
(532, 93, 'easy', 6, 13, '2025-03-18 02:44:05', 0, 332),
(533, 93, 'hard', 60, 219, '2025-03-18 02:47:50', 0, 241),
(534, 81, 'easy', 3, 4, '2025-04-24 15:41:49', 0, 332),
(535, 96, 'easy', 13, 44, '2025-06-06 11:50:21', 0, 302);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `level` int(11) DEFAULT 1,
  `xp` int(11) DEFAULT 0,
  `current_streak` int(11) DEFAULT 0,
  `last_played` date DEFAULT NULL,
  `last_reward_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `created_at`, `level`, `xp`, `current_streak`, `last_played`, `last_reward_at`) VALUES
(81, 'rma', 'q@gmail.com', '$2y$10$df.fj1Z6haBTPkkuVfaure/Bfp9bQaXeK8QDQQwD9aK8bFzc9O3E2', '2025-03-10 13:01:10', 5, 4543, 0, '2025-06-24', NULL),
(83, 'viprafathar04', 'l_nurcahyo@yahoo.co.id', '$2y$10$.W7Hh7by31E5aXdagADkpOOlAqM2GqUg4i0X4w9dpJbJlCE4NgGQW', '2025-03-12 15:34:38', 1, 0, 0, NULL, NULL),
(84, 'Aryasuta', 'aryasutamadana@student.telkomuniversity.ac.id', '$2y$10$nvr1wuSX.UA82HTcpHwLcOB3Qw3I9Hi6T3mW..dR5zA71zqZ4Z/g2', '2025-03-13 13:31:53', 2, 1541, 4, '2025-03-18', NULL),
(85, 'Hanif', 'hanifhermadi1607@gmail.com', '$2y$10$DbQk1AWrfryDtWdhu8xqNOrqB9yUfAuPmn7RGBArgfP0BPGS/Vg3C', '2025-03-13 15:45:48', 5, 4580, 0, '2025-06-24', NULL),
(86, 'Fadhil', 'rinandafadhil@gmail.com', '$2y$10$cg7bpUn3v.a0FA/qN4DP9OG088/aRyzJeZxGkfk.OhN/MtJ.7LLIW', '2025-03-15 05:14:53', 3, 2693, 0, '2025-06-24', NULL),
(87, 'Razquel', 'rasseldude21@gmail.com', '$2y$10$YpolM6Xs67WvVDK20bZHT.woCvKRtLEyz./DyKbp0nUHU4q75L7NC', '2025-03-15 06:09:13', 11, 9360, 0, '2025-06-24', NULL),
(88, 'putrriesky', 'putrieeerr@gmail.com', '$2y$10$wGeV/xfPaCmCAa97aXY5veoUu7atHZTgBYiNh74zGk/hVQ03PT4zK', '2025-03-15 15:30:07', 8, 7236, 0, '2025-06-24', NULL),
(89, 'Rreen6', 'aizenancrit@gmail.com', '$2y$10$27BJN.WUU4gYCsTUMfazvuvixxtbsylasE3ZSeojT0HLZ.I/n3z6C', '2025-03-15 16:43:40', 12, 9866, 0, '2025-06-24', NULL),
(90, 'iqbalad', 'iqbalandhikad@gmail.com', '$2y$10$YPXIspC.06BsZcMol4t4O.euzMcoQhoX/AjXdph7gGLOTzZC.6oF.', '2025-03-15 23:59:39', 12, 11401, 0, '2025-06-24', NULL),
(91, 'Petrik', 'radithya.arsono@gmail.com', '$2y$10$gfknLI3sjveliAvov2A9JueDz5iQTOEblKszQsGDh/KY2ealUVWVe', '2025-03-16 11:05:38', 12, 9738, 0, '2025-06-24', NULL),
(92, 'novrihabibullah', 'novrihabibullah7@gmail.com', '$2y$10$m.MmOpUv8IfcU0nFGg1uHeyNtnoGtCXXJPj6MjskoYr/eXHJc8TUW', '2025-03-16 11:40:57', 14, 12956, 0, '2025-06-24', NULL),
(93, 'bagas', 'aryobagaskoro93@gmail.com', '$2y$10$.kcSte2ARmMU7snw.hRWLO25xyqRC9Q5PPmKRqWozHeAjIT0Mbq5K', '2025-03-18 02:41:22', 2, 1517, 0, '2025-06-24', NULL),
(95, 'Jokopra', 'hilalnuha@gmail.com', '$2y$10$IjoOGx3PM9EipIPHAZGm5OXurY5fDBtr7XWq5/.jfRFGBBybH41UC', '2025-06-06 11:38:17', 1, 0, 0, NULL, NULL),
(96, 'tes', 'test@gmail.com', '$2y$10$3jUXFpxdXtE1/z63pdjNu.qGXxtVla7NSpkzGKH768huCG7FWkija', '2025-06-06 11:48:58', 1, 302, 0, '2025-06-06', NULL),
(97, 'test', 'tesst@gmail.com', '$2y$10$GQDF8TDa5Mj2G1fb2lDZ/.HZnMnHEDPAp.TL7fmXcmCTahOzDZSEC', '2025-06-06 14:51:20', 1, 0, 0, NULL, NULL),
(98, 'hilalgame', 'hilalnuha@outlook.org', '$2y$10$6ndae2H0KhMvNXL9aaits.PMMkeTdMaAe55J6sz19z1vDyo7g3dGy', '2025-06-24 07:43:09', 1, 0, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_challenge_progress`
--

CREATE TABLE `user_challenge_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `challenge_id` int(11) DEFAULT NULL,
  `progress` int(11) DEFAULT 0,
  `completed` tinyint(1) DEFAULT 0,
  `notified` tinyint(1) DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp(),
  `daily_count` int(11) DEFAULT 0,
  `last_count_date` date DEFAULT NULL,
  `progress_increment` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_challenge_progress`
--

INSERT INTO `user_challenge_progress` (`id`, `user_id`, `challenge_id`, `progress`, `completed`, `notified`, `last_updated`, `daily_count`, `last_count_date`, `progress_increment`) VALUES
(8950, 81, 17, 0, 0, 0, '2025-04-24 15:41:49', 1, NULL, 1),
(8951, 81, 13, 0, 0, 0, '2025-04-24 15:41:49', 0, NULL, 1),
(8952, 81, 14, 0, 0, 0, '2025-04-24 15:41:49', 0, NULL, 1),
(8953, 81, 16, 0, 0, 0, '2025-04-24 15:41:49', 0, NULL, 1),
(8954, 84, 17, 0, 0, 0, '2025-03-13 13:37:41', 1, NULL, 1),
(8955, 84, 14, 0, 0, 0, '2025-03-13 13:32:49', 0, NULL, 1),
(8956, 84, 16, 0, 0, 0, '2025-03-13 13:37:41', 0, NULL, 1),
(8957, 84, 15, 0, 0, 0, '2025-03-13 13:35:49', 0, NULL, 1),
(8958, 84, 13, 0, 0, 0, '2025-03-13 13:37:41', 0, NULL, 1),
(8959, 85, 17, 0, 0, 0, '2025-03-14 17:15:45', 1, NULL, 1),
(8960, 85, 14, 0, 0, 0, '2025-03-14 10:09:40', 0, NULL, 1),
(8961, 85, 16, 0, 0, 0, '2025-03-14 17:15:45', 0, NULL, 1),
(8962, 85, 12, 0, 0, 0, '2025-03-14 10:10:53', 0, NULL, 1),
(8963, 85, 13, 0, 0, 0, '2025-03-14 17:15:23', 0, NULL, 1),
(8964, 86, 17, 0, 0, 0, '2025-03-15 05:23:26', 1, NULL, 1),
(8965, 86, 14, 0, 0, 0, '2025-03-15 05:16:11', 0, NULL, 1),
(8966, 86, 13, 0, 0, 0, '2025-03-15 05:18:15', 0, NULL, 1),
(8967, 86, 16, 0, 0, 0, '2025-03-15 05:19:19', 0, NULL, 1),
(8968, 86, 15, 0, 0, 0, '2025-03-15 05:23:26', 0, NULL, 1),
(8969, 87, 17, 0, 0, 0, '2025-03-17 04:39:19', 3, NULL, 1),
(8970, 87, 13, 0, 0, 0, '2025-03-17 04:39:19', 0, NULL, 1),
(8971, 87, 14, 0, 0, 0, '2025-03-17 04:38:41', 0, NULL, 1),
(8972, 87, 16, 0, 0, 0, '2025-03-17 04:39:19', 0, NULL, 1),
(8973, 87, 12, 0, 0, 0, '2025-03-15 06:12:22', 0, NULL, 1),
(8974, 88, 17, 0, 0, 0, '2025-03-15 15:40:19', 1, NULL, 1),
(8975, 88, 14, 0, 0, 0, '2025-03-15 15:31:03', 0, NULL, 1),
(8976, 88, 16, 0, 0, 0, '2025-03-15 15:40:19', 0, NULL, 1),
(8977, 88, 13, 0, 0, 0, '2025-03-15 15:40:19', 0, NULL, 1),
(8978, 90, 17, 0, 0, 0, '2025-03-16 00:09:26', 3, NULL, 1),
(8979, 90, 13, 0, 0, 0, '2025-03-16 00:09:26', 0, NULL, 1),
(8980, 90, 14, 0, 0, 0, '2025-03-16 00:00:16', 0, NULL, 1),
(8981, 90, 16, 0, 0, 0, '2025-03-16 00:09:26', 0, NULL, 1),
(8982, 90, 12, 0, 0, 0, '2025-03-16 00:09:17', 0, NULL, 1),
(8983, 89, 17, 0, 0, 0, '2025-03-16 06:34:28', 3, NULL, 1),
(8984, 89, 14, 0, 0, 0, '2025-03-16 06:23:39', 0, NULL, 1),
(8985, 89, 13, 0, 0, 0, '2025-03-16 06:34:06', 0, NULL, 1),
(8986, 89, 16, 0, 0, 0, '2025-03-16 06:34:06', 0, NULL, 1),
(8987, 91, 17, 0, 0, 0, '2025-03-16 11:15:52', 3, NULL, 1),
(8988, 91, 13, 0, 0, 0, '2025-03-16 11:15:52', 0, NULL, 1),
(8989, 91, 14, 0, 0, 0, '2025-03-16 11:06:35', 0, NULL, 1),
(8990, 91, 16, 0, 0, 0, '2025-03-16 11:15:52', 0, NULL, 1),
(8991, 91, 15, 0, 0, 0, '2025-03-16 11:13:13', 0, NULL, 1),
(8992, 92, 17, 0, 0, 0, '2025-03-16 12:11:16', 4, NULL, 1),
(8993, 92, 14, 0, 0, 0, '2025-03-16 11:43:25', 0, NULL, 1),
(8994, 92, 13, 0, 0, 0, '2025-03-16 12:11:16', 0, NULL, 1),
(8995, 92, 16, 0, 0, 0, '2025-03-16 12:11:16', 0, NULL, 1),
(8996, 92, 15, 0, 0, 0, '2025-03-16 11:57:12', 0, NULL, 1),
(8997, 93, 17, 0, 0, 0, '2025-03-18 02:47:50', 1, NULL, 1),
(8998, 93, 14, 0, 0, 0, '2025-03-18 02:42:55', 0, NULL, 1),
(8999, 93, 16, 0, 0, 0, '2025-03-18 02:44:05', 0, NULL, 1),
(9000, 93, 13, 0, 0, 0, '2025-03-18 02:44:05', 0, NULL, 1),
(9001, 93, 15, 0, 0, 0, '2025-03-18 02:47:50', 0, NULL, 1),
(9002, 96, 17, 0, 0, 0, '2025-06-06 11:50:21', 1, NULL, 1),
(9003, 96, 14, 0, 0, 0, '2025-06-06 11:50:21', 0, NULL, 1),
(9004, 96, 16, 0, 0, 0, '2025-06-06 11:50:21', 0, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `weekly_challenges`
--

CREATE TABLE `weekly_challenges` (
  `id` int(11) NOT NULL,
  `challenge_type` enum('ACHIEVEMENT_HUNTER','SPEED_MASTER','PERFECT_SOLVER','DAILY_PLAYER','COMBO_MASTER','PRECISION_KING') DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `target` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reward_xp` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `weekly_challenges`
--

INSERT INTO `weekly_challenges` (`id`, `challenge_type`, `title`, `description`, `target`, `start_date`, `end_date`, `created_at`, `reward_xp`) VALUES
(12, 'SPEED_MASTER', 'Speed Master', 'Complete 5 puzzles under 80 seconds in Medium difficulty', 5, '2025-06-24', '2025-07-01', '2025-02-04 11:11:13', 500),
(13, 'PERFECT_SOLVER', 'Perfect Solver', 'Complete 3 puzzles with minimum moves', 3, '2025-06-24', '2025-07-01', '2025-02-04 11:11:13', 750),
(14, 'DAILY_PLAYER', 'Daily Challenge', 'Play 7 days in a row', 7, '2025-06-24', '2025-07-01', '2025-02-04 11:11:13', 1000),
(15, 'COMBO_MASTER', 'Hard Mode Master', 'Complete 3 puzzles in Hard Level', 3, '2025-06-24', '2025-07-01', '2025-02-04 11:11:13', 800),
(16, 'PRECISION_KING', 'Precision King', 'Complete 5 puzzles with less than 15 moves', 5, '2025-06-24', '2025-07-01', '2025-02-04 11:11:13', 900),
(17, 'ACHIEVEMENT_HUNTER', 'Achievement Hunter', 'Earn 3 achievements in a day', 3, '2025-06-24', '2025-07-01', '2025-02-04 11:11:14', 1200),
(18, '', 'Weekly Challenge', 'Complete 10 puzzles with minimum moves', 10, '2025-06-24', '2025-07-01', '2025-04-18 02:37:21', 0),
(19, '', 'Weekly Challenge', 'Complete 10 puzzles with minimum moves', 10, '2025-06-24', '2025-07-01', '2025-04-27 11:45:42', 0),
(20, '', 'Weekly Challenge', 'Complete 10 puzzles with minimum moves', 10, '2025-06-24', '2025-07-01', '2025-06-06 11:38:41', 0),
(21, '', 'Weekly Challenge', 'Complete 10 puzzles with minimum moves', 10, '2025-06-24', '2025-07-01', '2025-06-24 07:43:32', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `achievements`
--
ALTER TABLE `achievements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_achievements` (`user_id`,`achievement_type`);

--
-- Indexes for table `challenge_rewards`
--
ALTER TABLE `challenge_rewards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_challenge_reward` (`user_id`,`challenge_id`),
  ADD UNIQUE KEY `unique_user_reward` (`user_id`,`challenge_id`),
  ADD KEY `challenge_id` (`challenge_id`);

--
-- Indexes for table `puzzle_scores`
--
ALTER TABLE `puzzle_scores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_scores` (`user_id`,`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user_last_played` (`last_played`);

--
-- Indexes for table `user_challenge_progress`
--
ALTER TABLE `user_challenge_progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `challenge_id` (`challenge_id`),
  ADD KEY `idx_user_challenge` (`user_id`,`challenge_id`,`completed`);

--
-- Indexes for table `weekly_challenges`
--
ALTER TABLE `weekly_challenges`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `achievements`
--
ALTER TABLE `achievements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `challenge_rewards`
--
ALTER TABLE `challenge_rewards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `puzzle_scores`
--
ALTER TABLE `puzzle_scores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=536;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `user_challenge_progress`
--
ALTER TABLE `user_challenge_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9005;

--
-- AUTO_INCREMENT for table `weekly_challenges`
--
ALTER TABLE `weekly_challenges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `achievements`
--
ALTER TABLE `achievements`
  ADD CONSTRAINT `achievements_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `challenge_rewards`
--
ALTER TABLE `challenge_rewards`
  ADD CONSTRAINT `challenge_rewards_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `challenge_rewards_ibfk_2` FOREIGN KEY (`challenge_id`) REFERENCES `weekly_challenges` (`id`);

--
-- Constraints for table `puzzle_scores`
--
ALTER TABLE `puzzle_scores`
  ADD CONSTRAINT `puzzle_scores_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_challenge_progress`
--
ALTER TABLE `user_challenge_progress`
  ADD CONSTRAINT `user_challenge_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_challenge_progress_ibfk_2` FOREIGN KEY (`challenge_id`) REFERENCES `weekly_challenges` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
