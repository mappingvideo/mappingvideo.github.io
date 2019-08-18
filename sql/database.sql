CREATE TABLE `mass_embedder` (
	`site` varchar(255) NOT NULL default '',
	`url` varchar(255) NOT NULL default '',
	KEY `site` (`site`),
	KEY `url` (`url`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
