
DROP TABLE IF EXISTS `pt_schema`;
CREATE TABLE IF NOT EXISTS `pt_schema` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tblname` text NOT NULL,
  `name` text NOT NULL,
  `title` text NOT NULL,
  `maxl` int(11) DEFAULT '0',
  `minl` int(11) DEFAULT '0',
  `des` text,
  `type` text,
  `pk` tinyint(1) DEFAULT '0',
  `ac` tinyint(1) DEFAULT '0',
  `uq` tinyint(1) DEFAULT '0',
  `defaults` text,
  `list` tinyint(1) DEFAULT '0',
  `listedit` tinyint(1) DEFAULT '0',
  `width` int(11) DEFAULT '0',
  `height` int(11) DEFAULT '0',
  `element` text,
  `sets` text,
  `enums` text,
  `dec_m` int(11) DEFAULT '0',
  `dec_d` int(11) DEFAULT '0',
  `ord` int(11) DEFAULT '0',
  `regx` text,
  `yz` text,
  `options` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `pt_tables`;
CREATE TABLE IF NOT EXISTS `pt_tables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `title` text,
  `charset` text,
  `engine` text,
  `auto_increment` int(11) DEFAULT NULL,
  `ord` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
