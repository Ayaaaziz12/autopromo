CREATE TABLE IF NOT EXISTS `PREFIX_autopromo_rules` (
    `id_rule` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `conditions` text NOT NULL,
    `actions` text NOT NULL,
    `active` tinyint(1) NOT NULL DEFAULT '1',
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_rule`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_autopromo_logs` (
    `id_log` int(11) NOT NULL AUTO_INCREMENT,
    `id_rule` int(11) NOT NULL,
    `id_customer` int(11) NULL,
    `id_product` int(11) NULL,
    `action_type` varchar(50) NOT NULL,
    `details` text NOT NULL,
    `date_add` datetime NOT NULL,
    PRIMARY KEY (`id_log`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_autopromo_generated_coupons` (
    `id_generated` int(11) NOT NULL AUTO_INCREMENT,
    `id_rule` int(11) NOT NULL,
    `id_cart_rule` int(11) NOT NULL,
    `id_customer` int(11) NULL,
    `date_add` datetime NOT NULL,
    PRIMARY KEY (`id_generated`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;