CREATE DATABASE IF NOT EXISTS `phpticket_advanced` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `phpticket_advanced`;

CREATE TABLE IF NOT EXISTS `accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` enum('Member','Admin') NOT NULL DEFAULT 'Member',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO `accounts` (`id`, `full_name`, `password`, `email`, `role`) VALUES
(1, 'Admin', '$2y$10$wXkhBmUEz7814.uAtHhYduoq.8WmFU3rRuwqc1k9xvSnB.OWj5aGq', 'admin@example.com', 'Admin');

CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO `categories` (`id`, `title`) VALUES
(1, 'General'),
(2, 'Technical'),
(3, 'Other');

CREATE TABLE IF NOT EXISTS `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `msg` text NOT NULL,
  `full_name` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `ticket_status` enum('open','closed','resolved') NOT NULL DEFAULT 'open',
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'low',
  `category_id` int(1) NOT NULL DEFAULT 1,
  `private` tinyint(1) NOT NULL DEFAULT 1,
  `account_id` int(11) NOT NULL DEFAULT 0,
  `approved` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO `tickets` (`id`, `title`, `msg`, `full_name`, `email`, `created`, `ticket_status`, `priority`, `category_id`, `private`, `account_id`, `approved`) VALUES
(1, 'How do I navigate to the website?', 'Hello, I\'m having trouble and need your help!', 'John Doe', 'johndoe@example.com', '2023-04-17 13:06:17', 'open', 'low', 1, 0, 0, 1),
(2, 'Website issue', 'I\'m having issues running the website on my laptop, can you help?', 'John Doe', 'johndoe@example.com', '2023-04-17 13:07:40', 'resolved', 'medium', 1, 0, 0, 1),
(3, 'Responsive design issue', 'I have noticed on mobile devices the website does not work correctly, will you guys fix this problem?', 'John Doe', 'johndoe@example.com', '2023-04-18 14:30:33', 'open', 'low', 1, 0, 0, 1),
(4, 'Navigation menu not aligned', 'When I browser the website on a mobile device I have noticed the menu is not aligned, just letting you guys know.', 'John Doe', 'johndoe@example.com', '2023-04-18 15:47:20', 'closed', 'high', 1, 0, 0, 1);

CREATE TABLE IF NOT EXISTS `tickets_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `msg` text NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `account_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `tickets_uploads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `filepath` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;