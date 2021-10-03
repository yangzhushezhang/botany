/*
 Navicat Premium Data Transfer

 Source Server         : botany
 Source Server Type    : MySQL
 Source Server Version : 50650
 Source Host           : 8.136.97.179:3306
 Source Schema         : botany

 Target Server Type    : MySQL
 Target Server Version : 50650
 File Encoding         : 65001

 Date: 03/10/2021 18:39:12
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for account_number
-- ----------------------------
DROP TABLE IF EXISTS `account_number`;
CREATE TABLE `account_number` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_value` text NOT NULL,
  `token_md5` varchar(255) NOT NULL,
  `status` int(1) NOT NULL DEFAULT '1',
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  `remark` varchar(255) NOT NULL,
  `leWallet` int(11) NOT NULL DEFAULT '0',
  `all_sunflower` int(11) DEFAULT '0',
  `all_sapling` int(11) DEFAULT '0',
  `already_sapling` int(11) DEFAULT '0',
  `already_sunflower` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for farm
-- ----------------------------
DROP TABLE IF EXISTS `farm`;
CREATE TABLE `farm` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_number_id` int(11) NOT NULL,
  `farm_id` varchar(255) NOT NULL,
  `harvestTime` int(11) NOT NULL,
  `needWater` int(1) NOT NULL,
  `hasSeed` int(1) NOT NULL,
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  `status` int(1) NOT NULL DEFAULT '1',
  `plant_type` int(1) NOT NULL DEFAULT '1',
  `stage` varchar(255) DEFAULT NULL,
  `totalHarvest` int(11) NOT NULL DEFAULT '0',
  `remove` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=414 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for log
-- ----------------------------
DROP TABLE IF EXISTS `log`;
CREATE TABLE `log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `content` varchar(255) NOT NULL,
  `kind` int(1) NOT NULL DEFAULT '1',
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  `account_number_id` int(11) DEFAULT '0',
  `variety` int(2) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1777 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for tools
-- ----------------------------
DROP TABLE IF EXISTS `tools`;
CREATE TABLE `tools` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_number_id` int(11) NOT NULL,
  `water` int(11) NOT NULL,
  `samll_pot` int(11) NOT NULL,
  `scarecrow` int(11) NOT NULL,
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for user
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `kind` int(1) NOT NULL DEFAULT '1',
  `status` int(1) NOT NULL DEFAULT '1',
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `API_KEY` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

SET FOREIGN_KEY_CHECKS = 1;
