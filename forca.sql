-- ==========================================================================
-- FORCAX - Dump SQL Pronto para Importação no InfinityFree (phpMyAdmin)
-- Banco de Destino: `if0_43041475_forca`
-- Domínio: forca.free.nf
-- Gerado em: 2026-11-20 19:13:56
-- ==========================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Estrutura para tabela `admins`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Despejando dados para a tabela `admins`
INSERT INTO `admins` (`id`, `username`, `password_hash`) VALUES ('1', 'admin', '$2y$10$4fathbJI9wfWyeq29c7J3OhAR3tV9qj9pSb7octXJB6W0MEDSfOtu');

-- --------------------------------------------------------
-- Estrutura para tabela `settings`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Despejando dados para a tabela `settings`
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('adsense_code', '');
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('adsterra_code', '');
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('adsterra_social_bar', '');
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('ad_provider', 'adsterra');
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('donate_link', '');

-- --------------------------------------------------------
-- Estrutura para tabela `sponsors`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `sponsors`;
CREATE TABLE `sponsors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image_url` varchar(255) NOT NULL,
  `target_link` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Estrutura para tabela `dictionary`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `dictionary`;
CREATE TABLE `dictionary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `word` varchar(50) NOT NULL,
  `language` varchar(10) NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `hint` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `word` (`word`,`language`)
) ENGINE=InnoDB AUTO_INCREMENT=280 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Despejando dados para a tabela `dictionary`
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('1', 'CARRO', 'pt', '0', 'Veículo automotor de quatro rodas para transporte de passageiros.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('2', 'AMOR', 'pt', '0', 'Sentimento de grande afeição, carinho e dedicação por alguém.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('3', 'FESTA', 'pt', '0', 'Reunião social para comemoração, alegria e diversão.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('4', 'NOITE', 'pt', '0', 'Período do dia compreendido entre o pôr e o nascer do sol.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('5', 'MUNDO', 'pt', '0', 'O planeta Terra com todos os seus países, oceanos e habitantes.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('6', 'TEMPO', 'pt', '0', 'Duração contínua dos acontecimentos medida em horas, dias e anos.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('7', 'LIVRO', 'pt', '0', 'Conjunto de folhas impressas e encadernadas para leitura.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('8', 'FLOR', 'pt', '0', 'Estrutura reprodutiva colorida e perfumada das plantas.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('9', 'BEIJO', 'pt', '0', 'Toque dos lábios como demonstração de afeto ou saudação.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('10', 'CHAVE', 'pt', '0', 'Utensílio de metal usado para abrir ou fechar fechaduras.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('11', 'VIDRO', 'pt', '0', 'Material sólido, transparente e frágil usado em janelas e copos.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('12', 'PLANO', 'pt', '0', 'Projeto ou conjunto de medidas organizadas para atingir um objetivo.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('13', 'CORPO', 'pt', '0', 'Estrutura física e orgânica de um ser humano ou animal.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('14', 'VENTO', 'pt', '0', 'Corrente de ar em movimento na atmosfera.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('15', 'CINTO', 'pt', '0', 'Tira de couro ou tecido usada na cintura para segurar a roupa.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('16', 'PRAIA', 'pt', '0', 'Faixa de areia à beira do mar ou de um rio.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('17', 'CAMPO', 'pt', '0', 'Terreno extenso fora da cidade, área rural ou de cultivo.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('18', 'FOGO', 'pt', '0', 'Combustão que produz calor, chamas e luminosidade.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('19', 'AGUA', 'pt', '0', 'Líquido transparente, inodoro e essencial para toda forma de vida.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('20', 'TERRA', 'pt', '0', 'Solo onde crescem as plantas; o terceiro planeta do Sistema Solar.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('21', 'ROUPA', 'pt', '0', 'Peça do vestuário usada para cobrir e proteger o corpo.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('22', 'BOLSO', 'pt', '0', 'Pequeno compartimento costurado na roupa para guardar objetos.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('23', 'PORTA', 'pt', '0', 'Abertura móvel que permite entrar ou sair de um ambiente.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('24', 'JANELA', 'pt', '0', 'Abertura na parede de um edifício para entrada de luz e ar.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('25', 'QUARTO', 'pt', '0', 'Cômodo de uma casa destinado ao descanso e a dormir.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('26', 'SALA', 'pt', '0', 'Ambiente principal de convivência e recepção em uma residência.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('27', 'PEDRA', 'pt', '0', 'Fragmento sólido de rocha ou mineral duro encontrado na natureza.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('28', 'AREIA', 'pt', '0', 'Conjunto de pequenos grãos minerais encontrados em praias e desertos.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('29', 'NUVEM', 'pt', '0', 'Massa visível de gotículas de água suspensa na atmosfera.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('30', 'CHUVA', 'pt', '0', 'Precipitação de água em forma de gotas que caem das nuvens.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('31', 'GATO', 'pt', '0', 'Pequeno felino doméstico ágil que mia e caça roedores.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('32', 'CACHORRO', 'pt', '0', 'Mamífero doméstico leal conhecido como o melhor amigo do homem.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('33', 'LEAO', 'pt', '0', 'Grande felino africano com juba, chamado de rei da selva.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('34', 'TIGRE', 'pt', '0', 'Grande felino asiático de pelagem alaranjada com listras pretas.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('36', 'COBRA', 'pt', '0', 'Réptil alongado e sem patas que rasteja pelo solo.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('37', 'SAPO', 'pt', '0', 'Anfíbio que vive perto da água, salta e se alimenta de insetos.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('38', 'PEIXE', 'pt', '0', 'Animal vertebrado aquático que respira por brânquias e tem nadadeiras.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('39', 'PASSARO', 'pt', '0', 'Ave geralmente de pequeno porte que voa e canta.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('40', 'AGUIA', 'pt', '0', 'Ave de rapina diurna conhecida pela visão aguçada e voo alto.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('41', 'PATO', 'pt', '0', 'Ave aquática de bico achatado que nada, voa e anda.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('42', 'GALINHA', 'pt', '0', 'Ave doméstica criada em quintais e granjas que bota ovos.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('43', 'PORCO', 'pt', '0', 'Mamífero doméstico de focinho achatado criado em fazendas.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('44', 'VACA', 'pt', '0', 'Mamífero bovino fêmea produtor de leite.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('45', 'BOI', 'pt', '0', 'Mamífero bovino macho usado na pecuária e no campo.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('46', 'CAVALO', 'pt', '0', 'Mamífero quadrúpede domesticado usado para montaria e tração.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('47', 'BURRO', 'pt', '0', 'Mamífero equino de orelhas longas muito resistente para carga.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('48', 'MULA', 'pt', '0', 'Animal híbrido resultante do cruzamento de jumento com égua.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('49', 'LENTO', 'pt', '0', 'Que se move devagar, com pouca velocidade.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('50', 'RAPIDO', 'pt', '0', 'Que se move com grande velocidade, veloz e ágil.');
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('51', 'VEERY', 'en', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('52', 'GREET', 'en', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('53', 'BAYED', 'en', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('54', 'VAILS', 'en', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('55', 'FREED', 'en', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('56', 'NEUKS', 'en', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('57', 'BIDET', 'en', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('58', 'SEXTO', 'en', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('59', 'SCADS', 'en', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('60', 'BENDS', 'en', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('61', 'BRINS', 'en', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('62', 'BYLAW', 'en', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('63', 'PLIED', 'en', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('64', 'SHONE', 'en', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('65', 'HIRES', 'en', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('66', 'LIEVER', 'en', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('67', 'CABMEN', 'en', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('68', 'WABBLY', 'en', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('69', 'BESTOW', 'en', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('70', 'ROOFIE', 'en', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('71', 'CYANIC', 'en', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('72', 'FLAMEN', 'en', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('73', 'NONYLS', 'en', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('74', 'WARING', 'en', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('75', 'OFFKEY', 'en', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('76', 'JIBBED', 'en', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('77', 'ZINCKY', 'en', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('78', 'COPOUT', 'en', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('79', 'TARPAN', 'en', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('80', 'MASONS', 'en', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('81', 'YEGGS', 'es', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('82', 'CEDIÓ', 'es', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('83', 'UNCIA', 'es', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('84', 'CAJÓN', 'es', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('85', 'VOCES', 'es', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('86', 'HILTS', 'es', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('87', 'SUAVE', 'es', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('88', 'FRISO', 'es', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('89', 'MAYOR', 'es', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('90', 'GANAR', 'es', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('91', 'MUJER', 'es', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('92', 'LÁSER', 'es', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('93', 'COUTH', 'es', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('94', 'RABIA', 'es', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('95', 'RELÉS', 'es', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('96', 'ANIMAL', 'es', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('97', 'SÚBETE', 'es', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('98', 'MUERTO', 'es', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('99', 'GRAMOS', 'es', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('100', 'CRASER', 'es', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('101', 'RATTAT', 'es', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('102', 'ESCOBA', 'es', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('103', 'COPIAS', 'es', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('104', 'BANGED', 'es', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('105', 'WILLIE', 'es', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('106', 'YUCKED', 'es', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('107', 'MALDAD', 'es', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('108', 'LENTOS', 'es', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('109', 'CASERO', 'es', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('110', 'MATRIZ', 'es', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('111', 'OSSÍVORO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('112', 'CONFIOSO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('113', 'PATADA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('114', 'ALGODÃO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('115', 'MARABU', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('116', 'FUMADOR', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('117', 'LIVRE', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('118', 'MINÉRIO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('119', 'EBONITE', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('120', 'SALMAÇO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('121', 'ANTIAR', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('122', 'PENÁCEO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('123', 'CONTISTA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('124', 'RE-SI', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('125', 'ILIÇADOR', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('126', 'FOLHEIO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('127', 'CUMARU', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('128', 'SACALHOS', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('129', 'ALBIONA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('130', 'DERRAMAR', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('131', 'PÓCHA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('132', 'RECURSO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('133', 'ARGAÇO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('134', 'ZYGOMA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('135', 'DECERCAR', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('136', 'FÁTSIA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('137', 'THEÓRICO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('138', 'ENRIÇADO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('139', 'MANELO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('140', 'SUDRO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('141', 'NONINHA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('142', 'GERMANAL', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('143', 'AFLAR', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('144', 'TAPERA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('145', 'RACIMADO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('146', 'DOUNDO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('147', 'LAMOSO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('148', 'TRINCHAR', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('149', 'VALAR', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('150', 'MILOCOCO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('151', 'GABARDO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('152', 'SINFONIA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('153', 'TIMÃO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('154', 'RUTELA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('155', 'COLHEITO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('156', 'SIRGILIM', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('157', 'EMBEBER', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('158', 'OBREGÃO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('159', 'RUBIEVA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('160', 'ASSARINA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('161', 'SCÍTALO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('162', 'AZOICO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('163', 'AHUME', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('164', 'TROMBA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('165', 'ASNARIA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('166', 'CUIPUNA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('167', 'JUDAIZAR', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('168', 'BEIRO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('169', 'RETORCE', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('170', 'PIPIO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('171', 'MEIGA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('172', 'CÔDEGA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('173', 'ESTIVA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('174', 'LOGRADOR', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('175', 'DENGUE', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('176', 'DÍZIMA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('177', 'TUBULITA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('178', 'SILLO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('179', 'PERPÉTUA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('180', 'COUVEIRO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('181', 'PASSAL', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('182', 'EMPOAR', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('183', 'SAPRINO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('184', 'FERRUGEM', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('185', 'FREIMA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('186', 'GULEIMA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('187', 'CHIQUITO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('188', 'NIQUIM', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('189', 'ABATINA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('190', 'MUBANGA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('191', 'RENTE', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('192', 'TOLDARIA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('193', 'FRÂNGULA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('194', 'LOBULADO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('195', 'RACONTO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('196', 'AMBALÓ', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('197', 'BINAGEM', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('198', 'GARNELA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('199', 'MELIFAGO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('200', 'TERRAÇO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('201', 'FÁBULA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('202', 'AROMA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('203', 'FOLGAZÃO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('204', 'GÁFIO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('205', 'SEPULCRO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('206', 'CORDA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('207', 'SYNODAL', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('208', 'ALTANADO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('209', 'BRAVISCO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('210', 'NARCEÍNA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('211', 'ARAPAPÁ', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('212', 'POSDATAR', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('213', 'BAZARUCO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('214', 'ADVERSÃO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('215', 'CUCHARRA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('216', 'ISOLÉPIS', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('217', 'EFFIGIAR', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('218', 'PODENGO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('219', 'URANOCRO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('220', 'ULTIMAR', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('221', 'FUCAMENA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('222', 'PULGA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('223', 'OLEOLADO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('224', 'FAGULHA', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('225', 'ENCUMEAR', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('226', 'NICOTICE', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('227', 'REGRAXO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('228', 'RELHEIRO', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('229', 'NEGUM', 'pt', '0', NULL);
INSERT INTO `dictionary` (`id`, `word`, `language`, `used`, `hint`) VALUES ('264', 'ONÇA', 'pt', '0', 'Maior felino das Américas, com pelagem amarela e manchas pintadas.');

-- --------------------------------------------------------
-- Estrutura para tabela `daily_words`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `daily_words`;
CREATE TABLE `daily_words` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `language` varchar(10) NOT NULL,
  `word_1` varchar(20) NOT NULL,
  `word_2` varchar(20) NOT NULL,
  `word_3` varchar(20) NOT NULL,
  `word_4` varchar(20) NOT NULL,
  `source` enum('dict','wiki') DEFAULT 'dict',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `hint_1` varchar(255) DEFAULT NULL,
  `hint_2` varchar(255) DEFAULT NULL,
  `hint_3` varchar(255) DEFAULT NULL,
  `hint_4` varchar(255) DEFAULT NULL,
  `word_5` varchar(255) DEFAULT NULL,
  `word_6` varchar(255) DEFAULT NULL,
  `word_7` varchar(255) DEFAULT NULL,
  `hint_5` varchar(255) DEFAULT NULL,
  `hint_6` varchar(255) DEFAULT NULL,
  `hint_7` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `date` (`date`,`language`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Despejando dados para a tabela `daily_words`
INSERT INTO `daily_words` (`id`, `date`, `language`, `word_1`, `word_2`, `word_3`, `word_4`, `source`, `created_at`, `hint_1`, `hint_2`, `hint_3`, `hint_4`, `word_5`, `word_6`, `word_7`, `hint_5`, `hint_6`, `hint_7`) VALUES ('1', '2026-09-03', 'pt', 'FOGO', 'BOLSO', 'VACA', 'VENTO', '', '2026-09-02 21:22:33', 'Desenvolvimento de luz e calor.', 'variedade de _____s especializados. O pequeno _____ frontal do jeans, conhecido ...', 'Fêmea do boi.', 'Corrente de ar, mais ou menos rápida, resultante das mudanças no peso específico...', 'CHAVE', 'CARRO', 'PORTA', 'Instrumento que, embebendo-se na abertura de uma fechadura, serve para abrir ou ...', 'Veículo de rodas, para transporte de coisas ou pessoas.', 'Abertura em parede, ao nível do chão ou de um pavimento, para dar entrada ou saí...');
INSERT INTO `daily_words` (`id`, `date`, `language`, `word_1`, `word_2`, `word_3`, `word_4`, `source`, `created_at`, `hint_1`, `hint_2`, `hint_3`, `hint_4`, `word_5`, `word_6`, `word_7`, `hint_5`, `hint_6`, `hint_7`) VALUES ('2', '2026-09-04', 'pt', 'GATO', 'BOI', 'ONÇA', 'CACHORRO', '', '2026-09-04 07:20:07', 'Animal doméstico, da ordem dos carnívoros digitígrados, (felis catus).', 'Espécie de ruminante, da fam. dos bovídeos, destinado principalmente a serviços ...', '\"C\'est Comme Ça\" é uma canção da banda de rock estadunidense Paramore, lançada c...', 'Cão novo e pequeno.', 'TERRA', 'PORCO', 'NOITE', 'Solo, sobre que se anda.', 'Quadrúpede da classe dos mamíferos e da ordem dos paquidermes.', 'Espaço de tempo, que vai desde o crepúsculo da tarde até o crepúsculo da manhã.');
INSERT INTO `daily_words` (`id`, `date`, `language`, `word_1`, `word_2`, `word_3`, `word_4`, `source`, `created_at`, `hint_1`, `hint_2`, `hint_3`, `hint_4`, `word_5`, `word_6`, `word_7`, `hint_5`, `hint_6`, `hint_7`) VALUES ('3', '2026-09-05', 'pt', 'GALINHA', 'SALA', 'FESTA', 'BEIJO', '', '2026-09-05 15:01:09', 'Fêmea do galo.', 'Um dos principais compartimentos de uma casa, ou o principal, destinado ordinari...', 'Propriamente, o dia feriado, dia de descanso, dia santificado, dia de regosijo.', 'Acção de chegar os labios fechados a alguém ou a alguma coisa, abrindo-os depois...', 'PEIXE', 'PLANO', 'PATO', 'Animal vertebrado, que nasce e vive na água, respirando por guelras.', 'Diz-se de qualquer superfície, que não tem desigualdades, nem curvas, nem ondula...', 'Ave palmípede, da fam. dos lamellirostos.');
INSERT INTO `daily_words` (`id`, `date`, `language`, `word_1`, `word_2`, `word_3`, `word_4`, `source`, `created_at`, `hint_1`, `hint_2`, `hint_3`, `hint_4`, `word_5`, `word_6`, `word_7`, `hint_5`, `hint_6`, `hint_7`) VALUES ('4', '2026-09-06', 'pt', 'OSSÍVORO', 'LENTO', 'MARABU', 'MINÉRIO', '', '2026-09-05 20:25:10', 'Palavra do dicionário', 'Que é pegajoso ou viscoso; mole.', 'Espécie de cegonha, (ciconia _____).', '(mineração). Pode ser uma rocha, sedimento ou solo. O minério é constituído de m...', 'CONFIOSO', 'CINTO', 'MUNDO', 'Cheio de confiança. Cf. Corvo, Ano na Corte, II, 139.', 'Correia, que cerca a cintura com uma só volta.', 'Conjunto de espaço, corpos e seres, que a vista humana pode abranger.');
INSERT INTO `daily_words` (`id`, `date`, `language`, `word_1`, `word_2`, `word_3`, `word_4`, `source`, `created_at`, `hint_1`, `hint_2`, `hint_3`, `hint_4`, `word_5`, `word_6`, `word_7`, `hint_5`, `hint_6`, `hint_7`) VALUES ('5', '2026-09-07', 'pt', 'GÁFIO', 'LOBULADO', 'DENGUE', 'PASSAL', '', '2026-09-06 19:01:30', 'O Grupo de Ação Financeira Internacional (GAFI; em inglês, Financial Action Task...', 'O mesmo que lobado.', 'Presumido.', 'Terreno cultivado, anexo e pertencente à residência de um pároco ou prelado.', 'FÁBULA', 'FREIMA', 'AROMA', ' Nota: Para outros significados, veja Fábula (desambiguação). As fábulas (em lat...', 'Impaciência.', 'Essência odorífera de várias substâncias vegetais.');
INSERT INTO `daily_words` (`id`, `date`, `language`, `word_1`, `word_2`, `word_3`, `word_4`, `source`, `created_at`, `hint_1`, `hint_2`, `hint_3`, `hint_4`, `word_5`, `word_6`, `word_7`, `hint_5`, `hint_6`, `hint_7`) VALUES ('6', '2026-11-20', 'pt', 'BAZARUCO', 'MELIFAGO', 'SEPULCRO', 'JUDAIZAR', '', '2026-11-20 12:39:17', 'Antiga moeda da Índia portuguesa.', 'Género de aves anisodáctilas.', 'Lugar, onde se enterram cadáveres.', 'Observar todos ou alguns dos ritos e leis dos Judeus.', 'BRAVISCO', 'ARAPAPÁ', 'CORDA', 'Um tanto bravo.', 'Garça-de-bico-largo ou arapapá (Cochlearius cochlearius) é uma ave pelecaniforme...', 'Peça de fios, unidos e torcidos uns sobre os outros.');

-- --------------------------------------------------------
-- Estrutura para tabela `players`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `players`;
CREATE TABLE `players` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(64) NOT NULL,
  `country_code` varchar(2) DEFAULT NULL,
  `country_name` varchar(100) DEFAULT NULL,
  `language` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=790 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Estrutura para tabela `games`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `games`;
CREATE TABLE `games` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `player_id` int(11) NOT NULL,
  `mode` tinyint(4) NOT NULL COMMENT '1: Forca, 2: Dueto, 4: Quarteto',
  `date` date NOT NULL,
  `is_win` tinyint(1) NOT NULL DEFAULT 0,
  `mistakes` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `guesses` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `player_id` (`player_id`,`date`,`mode`),
  CONSTRAINT `games_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=150 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Estrutura para tabela `rooms`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_code` varchar(10) NOT NULL,
  `host_uuid` varchar(64) NOT NULL,
  `mode` int(11) DEFAULT 1,
  `max_mistakes` int(11) DEFAULT 6,
  `language` varchar(10) DEFAULT 'pt-br',
  `word` varchar(100) DEFAULT NULL,
  `words` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`words`)),
  `status` varchar(20) DEFAULT 'waiting',
  `is_private` tinyint(1) DEFAULT 0,
  `current_turn` varchar(64) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `max_players` int(11) NOT NULL DEFAULT 4,
  `started_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_code` (`room_code`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Estrutura para tabela `room_players`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `room_players`;
CREATE TABLE `room_players` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `uuid` varchar(64) NOT NULL,
  `player_name` varchar(50) NOT NULL,
  `mistakes` int(11) DEFAULT 0,
  `words_completed` int(11) DEFAULT 0,
  `finished` tinyint(1) DEFAULT 0,
  `joined_at` datetime DEFAULT current_timestamp(),
  `last_seen` datetime DEFAULT current_timestamp(),
  `is_cohost` tinyint(1) DEFAULT 0,
  `score` int(11) DEFAULT 0,
  `last_action` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_room_player` (`room_id`,`uuid`),
  CONSTRAINT `room_players_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=157 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Estrutura para tabela `room_requests`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `room_requests`;
CREATE TABLE `room_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) DEFAULT NULL,
  `player_name` varchar(50) DEFAULT NULL,
  `uuid` varchar(255) DEFAULT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `room_requests_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Estrutura para tabela `scores`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `scores`;
CREATE TABLE `scores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `player` varchar(50) NOT NULL,
  `points` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Estrutura para tabela `multiplayer_games`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `multiplayer_games` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `match_id` varchar(64) NOT NULL,
  `room_code` varchar(10) NOT NULL,
  `player_uuid` varchar(64) NOT NULL,
  `player_name` varchar(50) NOT NULL,
  `is_win` tinyint(1) NOT NULL DEFAULT 0,
  `is_draw` tinyint(1) NOT NULL DEFAULT 0,
  `score` int(11) NOT NULL DEFAULT 0,
  `mistakes` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_match_player` (`match_id`, `player_uuid`),
  KEY `idx_player_uuid` (`player_uuid`),
  KEY `idx_score` (`score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
