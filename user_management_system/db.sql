create user 'slicktext'@'localhost' IDENTIFIED BY 'slick';
grant all privileges on slicktext_interview.* to 'slicktext'@'localhost';

create database slicktext_interview;
use slicktext_interview;

CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(128) DEFAULT NULL,
  `last_name` varchar(128)DEFAULT NULL,
  `email` varchar(128)DEFAULT NULL,
  `mobile_number` varchar(32)DEFAULT NULL,
  `address` varchar(128)DEFAULT NULL,
  `city` varchar(128)DEFAULT NULL,

  # Changed from 2 characters for international support
  `state` varchar(64)DEFAULT NULL,
  
  `zip` int DEFAULT NULL,
  
  # Also changed from 2 characters for international support
  `country` varchar(64)DEFAULT NULL,
  
  `timezone` varchar(32)DEFAULT NULL,
  `created` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);
