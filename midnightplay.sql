-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 29 Jan 2026 pada 21.08
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.4.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `midnightplay`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `carts`
--

CREATE TABLE `carts` (
  `id_cart` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_game` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `added_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','purchased','removed') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `games`
--

CREATE TABLE `games` (
  `id_game` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `genre` varchar(50) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `price` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive','draft') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `purchase_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `games`
--

INSERT INTO `games` (`id_game`, `title`, `genre`, `image_url`, `price`, `description`, `status`, `created_at`, `purchase_count`) VALUES
(1, 'EA SPORTS FC 26', 'Simulation,Sports', '1768989503_EGS_EASPORTSFC26StandardEdition_EACANADA_S1_2560x1440-efabe29766334696db018632ea5ba492.jpg', 799000, 'The Club is Yours.\r\nPlay your way with an overhauled gameplay experience powered by community feedback. The new Authentic Gameplay preset delivers the most true-to-football experience ever in Career, while the Competitive Gameplay preset—driven by refined fundamentals, added consistency, and enhanced responsiveness—is tailor-made for play in Football Ultimate Team™ and Clubs.\r\n\r\nManager Live\r\nExperience Manager Career like never before with all-new Manager Live Challenges. Earn rewards throughout the new season by taking on a variety of real-world scenarios and alternative storylines – ranging from a few minutes of play to multiple seasons.\r\n\r\nFootball Ultimate Team™\r\nPut your dream squad to the test in Football Ultimate Team™ with new Live Events and Tournament modes, as well as a refreshed Rivals and Champs experience.\r\n\r\nArchetypes\r\nArchetypes inspired by the greats of the game introduce new classes to Clubs and Player Career, bringing more individuality to your player. Develop your abilities by upgrading attributes and unlocking Archetype Perks to give your player a distinct feel on the pitch.\r\n\r\nUnrivalled Authenticity\r\nExperience unrivalled authenticity in EA SPORTS FC™ 26, with match data from the world’s top leagues powering 20,000+ authentic players.', 'active', '2026-01-21 06:50:56', 0),
(2, 'Grand Theft Auto V', 'Action,Adventure,Open World', '1768980729_gta-5-error-f429b1dacfdaeefa70d287988e2a1cd0.avif', 439000, 'Grand Theft Auto V Enhanced\r\nWelcome to Los Santos\r\nWhen a young street hustler, a retired bank robber, and a terrifying psychopath find themselves entangled with some of the most frightening and deranged elements of the criminal underworld, the U.S. government, and the entertainment industry, they must pull off a series of dangerous heists to survive in a ruthless city in which they can trust nobody — least of all each other.\r\nCurrent PC players can transfer both GTAV Story Mode progress and GTA Online characters and progression with a one-time migration from the legacy version of GTAV to GTAV Enhanced.\r\n\r\nStunning Visuals\r\nEnhanced levels of fidelity and performance with new graphics modes including ray tracing features such as ambient occlusion and global illumination, ray traced shadows and reflections, support for AMD FSR1 and FSR3, and NVIDIA DLSS 3, and more.*\r\n\r\nFaster Loading\r\nQuicker access to the action as the world of Los Santos and Blaine County load in faster than ever before by using SSD and DirectStorage on supported devices.\r\n\r\nImmersive Controls\r\nFeel new levels of responsiveness with dynamic resistance via the Adaptive Triggers on the DualSense™ wireless controller, from directional damage to weather effects, rough road surfaces to explosions, and much more.*\r\n\r\n3D Audio\r\nEnhanced audio with support for Dolby Atmos and improved fidelity of speech, cinematics, and music.* Hear the sounds of the world with pinpoint precision: the throttle of a stolen supercar, the rattle of neighboring gunfire, the roar of a helicopter overhead, and more.\r\n\r\nGrand Theft Auto Online\r\nExperience GTA Online, a dynamic and ever-evolving online universe for up to 30 players, where you can rise from street-level hustler to become a kingpin of your own criminal empire.\r\n\r\nEnjoy new high-performance vehicle upgrades and improvements like the Career Builder as well as all GTA Online gameplay upgrades, expansions, and content released since launch, ready to enjoy solo or with friends. Pull off d', 'active', '2026-01-21 07:09:29', 0),
(4, 'The Sims™ 4 ', 'Simulation', '1768988498_capsule_616x353.jpg', 479000, 'Create Unique Sims\r\nA variety of Sims are yours to shape and mold, each with distinct appearances, dynamic personalities and inspiring aspirations. Use powerful customization features to bring your imagination to life. Create yourself, your favorite celebrities, your fantasy or your friends! Change your Sims’ clothing to reflect your mood. Give your Sims depth and purpose with quirky traits and great ambitions.\r\n\r\nBuild the Perfect Home\r\nDesign the ideal homes for your Sims using Build Mode. Construct the home of your dreams by planning the layout, choosing furnishings and altering the landscape and terrain. You can even add a pool, basement and garden. Hate what you’ve done with the place? Scrap it and rebuild effortlessly with new ideas and designs.\r\n\r\nExplore Vibrant Worlds\r\nYour Sims can visit new communities to expand their social circle, hang out with friends or throw unforgettable parties.\r\n\r\nPlay with Life\r\nYour choices shape every aspect of your Sims’ lives, from birth to adulthood. Along the way, develop skills, pursue hobbies, find your Sims’ calling, start new families and much more.\r\n\r\nDiscover A Community of Creators\r\nUse the Gallery to find inspiration from a network of players just like you, where you can add content to your game or share your own creations. Download, like and comment on your favorite Sims, homes and fully designed rooms. Join the community and join the fun!', 'active', '2026-01-21 09:41:38', 0),
(5, 'LEGO® Star Wars™: The Skywalker Saga', 'Action,Adventure', '1768998258_header.jpg', 622000, 'LEGO® Star Wars™: The Skywalker Saga\r\nThe galaxy is yours in LEGO® Star Wars™: The Skywalker Saga. Experience memorable moments and nonstop action from all nine Skywalker saga films reimagined with signature LEGO humor.\r\n\r\nThe digital edition includes an exclusive classic Obi-Wan Kenobi playable character.\r\n\r\nExplore the Trilogies in Any Order\r\nPlayers will relive the epic story of all nine films in the Skywalker Saga, and it all starts with picking the trilogy of their choice to begin the journey.\r\n\r\nPlay as Iconic Heroes and Villains\r\nMore than 300 playable characters from throughout the galaxy.\r\n\r\nDiscover Legendary Locales\r\nPlayers can visit well known locales from their favorite Skywalker saga films .They can unlock and have the freedom to seamlessly travel to 23 planets as they play through the saga or explore and discover exciting quests.\r\n\r\nCommand Powerful Vehicles\r\nMore than 100 vehicles from across the galaxy to command. Join dogfights and defeat capital ships like the Super Star Destroyer that can be boarded and explored.\r\n\r\nImmersive Player Experiences\r\nString attacks together to form combo chains and fend off oncoming attacks. New blaster controls and mechanics allow players to aim with precision, or utilize the skills of a Jedi by wielding a lightsaber and using the power of The Force.\r\n\r\nUpgradable Character Abilities\r\nExploration rewards players as they uncover Kyber Bricks which unlock new features and upgraded abilities across a range of character classes, including Jedi, Hero, Dark Side, Villain, Scavenger, Scoundrel, Bounty Hunter, Astromech Droid, and Protocol Droid.', 'active', '2026-01-21 12:24:18', 0),
(6, 'Rocket League®', 'Racing', '1768998488_2x1_NSwitchDS_RocketLeague_S16_image1600w.jpg', 0, 'Rocket League®\r\nPLAY ROCKET LEAGUE FOR FREE!\r\n\r\nDownload and compete in the high-octane hybrid of arcade-style soccer and vehicular mayhem! customize your car, hit the field, and compete in one of the most critically acclaimed sports games of all time! Download and take your shot!\r\n\r\nHit the field by yourself or with friends in 1v1, 2v2, and 3v3 Online Modes, or enjoy Extra Modes like Rumble, Snow Day, or Hoops. Unlock items in Rocket Pass, climb the Competitive Ranks, compete in Competitive Tournaments, complete Challenges, enjoy cross-platform progression and more! The field is waiting. Take your shot!\r\n\r\nNew Challenges\r\nComplete Weekly and season-long Challenges to unlock customization items for free!\r\n\r\n\r\nTournaments\r\nFeel the competitive energy! Join free Tournaments and compete all season against teams at your Rank! Win and earn new rewards!\r\n\r\n\r\nIn-Game Events and Limited Time Modes\r\nFrom Haunted Hallows to Frosty Fest, enjoy limited time events that feature festive in-game items that can be unlocked by playing online! Keep on the lookout for Limited Time Modes and arenas.\r\n\r\n\r\nCross-Platform Progression\r\nShare your Rocket League Inventory, Competitive Rank, and Rocket Pass Tier on any connected platform!\r\n\r\n\r\nItem Shop & Blueprints\r\nMake your car your own with nearly endless customization possibilities! Get in-game items for completing challenges, browse the Item Shop, or build Blueprints for premium content for your car.', 'active', '2026-01-21 12:28:08', 0),
(7, 'PC Building Simulator', 'Idle,Simulation', '1768998645_images (5).jpg', 107999, 'PC Building Simulator\r\nTHE ULTIMATE PC BUILDING SIMULATION HAS ARRIVED\r\nBuild your very own PC empire, from simple diagnosis and repairs to bespoke, boutique creations that would be the envy of any enthusiast. With an ever-expanding marketplace full of real-world components you can finally stop dreaming of that ultimate PC and get out there, build it and see how it benchmarks in 3DMark!\r\n\r\nPC Building Simulator has already enjoyed viral success with over 650,000+ downloads of its pre-alpha demo and has now been lovingly developed into a fully-fledged simulation to allow you to build the PC of your dreams.\r\n\r\nRUN YOUR OWN BUSINESS\r\nThe career mode in PC Building Simulator puts you in charge of your very own PC building and repair business. From your own cozy workshop, you must use all your technical skills to complete the various jobs that come your way.\r\nCustomers will provide you with a range of jobs from simple upgrades and repairs to full system builds which you must complete while balancing your books to ensure you are still making a profit!\r\n\r\nBUILD YOUR DREAM PC\r\nBuild your PC from the case up with your favourite parts and express your building flair by choosing your favourite LED and cabling colors to really make it stand out. Choose from a range of air and water cooling solutions to keep it cool or even go all out with fully customizable water cooling loops! Once your rig is ready to go, turn it on and see how it benchmarks. Not happy with the results? Jump into the bios and try your hand at overclocking to see if you can get better results without breaking anything!\r\n\r\nLEARN TO BUILD A PC\r\nDoes building your own PC seem like an impossible task?\r\nPC Building Simulator aims to teach even the most novice PC user how their machine is put together with step-by-step instructions explaining the order parts should be assembled and providing useful information on what each part is and its function.', 'active', '2026-01-21 12:30:45', 0),
(8, 'Kellan Graves: Fallen', 'Firts Person,Indie,Shooter', '1768998874_capsule_616x353 (1).jpg', 34999, 'A story-driven sci-fi journey where a lone soldier descends into Elior’s depths and uncovers the silent hand of the Veiled Synod.\r\nKellan Graves: Fallen is a linear, story-driven science-fiction adventure set in the shifting cave systems deep beneath the surface of Elior. After surviving a fall into an unmapped tunnel, Echo-6 operative Kellan Graves discovers that the caves are far more than a natural formation. They function as a controlled environment shaped, observed and manipulated by the mysterious Veiled Synod.\r\n\r\nCut off from command and armed only with a handgun, Kellan must navigate a network of hostile caverns filled with traps, environmental challenges and the remains of past expeditions. Every chamber presents new tasks and objectives that gradually reveal the Synod’s true intentions.\r\n\r\nExploration, survival and progression become inseparable as Kellan follows the traces of Echo-4 and Echo-7, overcoming obstacles, discovering encoded logs and facing the creatures that lurk in the dark. The deeper he descends, the clearer it becomes that the Synod has been here long before him. And they are still watching.', 'active', '2026-01-21 12:34:34', 0),
(9, 'ARC Raiders', 'Action-Adventure,Shooter,Survival', '1769705069_ARC Raiders.jpg', 463200, 'ABOUT THIS GAME\r\nSCAVENGE, SURVIVE, THRIVE\r\n\r\nIn ARC Raiders, gameplay flows between the surface ruled by lethal machines, and the vibrant underground society of Speranza. Craft, repair, and upgrade your gear in the safety of your own workshop, before venturing topside to scavenge the remnants of a devastated but beautiful world. Play solo or in parties up to three, navigating the constant threat of ARC\'s machines and the unpredictable choices of fellow survivors. In the end, only you decide what kind of Raider you are - and how far you’ll go to prevail.\r\n\r\nEXPLORE AN IMMERSIVE WORLD\r\n\r\nExplore four distinct maps at launch, with more to be revealed as the underground society evolves and expands its reach. Each destination carries the weight of a world twice-destroyed, and the scars of conflicts both old and new. Sift through the remnants for valuable loot, and piece together the past as it\'s slowly reclaimed by nature. Evolving map conditions ensure that no two runs are the same, with varying weather, enemies, and mechanics adding unpredictability and danger.\r\n\r\nSTAKE YOUR CLAIM\r\n\r\nIn a society built on boldness and bravado, it is up to you to stake your claim as a Raider. The loot you scavenge can be sold for coin or crafted into all-new gear, allowing you take on increasingly lethal dangers head-on. Through both victories and hardships, you\'ll gain valuable experience; unlocking varied skills that enable all-new ways of play. In addition, you\'ll complete quests for Traders with differing motives and agendas; uncovering both the friction and comradery of a community under constant threat of collapse.\r\n\r\nBEWARE THE MACHINES\r\n\r\nLethal machines known as ARC rule the surface, ranging from unrelenting drone swarms to mechanical giants that obliterate everything in their path. Their origins remain a mystery, but their ever-present danger is felt with every step you take. Each machine comes with distinct strengths and tactics, forcing you to pinpoint their weak spots and constantly think on your feet. And remember: the noise of battle carries. Other Raiders may be listening; eager to claim what you leave behind.\r\n\r\nFORGE YOUR OWN PATH\r\n\r\nRaiders survive by kitbashing scavenged materials; using long-lost tech and looted ARC parts to craft weapons, gadgets, and gear. Upgrade your workshop stations and learn blueprints to craft even more advanced items, or improvise quick fixes in the field to get yourself out of a bind. As you make a name for yourself, you\'ll be able to measure your skill against other Raiders by taking on Trials; rising through the leaderboards to earn valuable rewards.\r\n\r\nQUESTS\r\n\r\nIn Speranza, everyone has an agenda, and the Traders are no different. They’ll send you topside on missions in exchange for rewards, slowly revealing more about who they are and what they want for Speranza’s future. Complete quests to earn gear, crafting materials, and XP to level up your Raider and unlock new skill points.\r\n\r\nSKILL TREE\r\n\r\nThe ARC Raiders skill tree branches into three paths: Survival, Mobility, and Conditioning. Spend your points to shape your playstyle. Loot faster and move quieter with Survival skills. Outmaneuver your opponents and threats with Mobility. And enhance your Strength and Stamina with Conditioning. Choose how you grow and what your Raider brings to the battle.\r\n\r\nBUILD YOUR ARSENAL\r\n\r\nWhether you’re preparing to take down specific ARC enemies or fend off rival Raiders, there\'s a choice of weaponry for every fight. Cater to your style with a range of firearms including SMGs, rifles and shotguns, as well as more advanced options such as railguns and energy weapons. Grenades, traps, ziplines and deployables offer the tactical depth needed to outsmart your enemies, while augments allow you to tailor your loadout to your preferred playstyle; unlocking dedicated inventory slots and additional gameplay perks.\r\n\r\nInternet connection, Embark ID user account (+13), third-party platform account, and acceptance of the Embark Terms of Service and End User License Agreement are required to play, see our user terms: https://id.embark.games/arc-raiders/support/section/user-terms(Opens in new tab). Age restrictions apply (13+).Raider Tokens are an in-game currency in ARC Raiders. Raider Tokens can be used to get raider decks, bundles, or other cosmetic items, including outfits, backpacks, charms, emotes, Scrappy outfits, etc.', 'active', '2026-01-29 16:44:29', 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `game_screenshots`
--

CREATE TABLE `game_screenshots` (
  `id_screenshot` int(11) NOT NULL,
  `id_game` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `game_screenshots`
--

INSERT INTO `game_screenshots` (`id_screenshot`, `id_game`, `image_url`, `caption`, `display_order`, `created_at`) VALUES
(1, 4, '1768988498_4_0_download1.jpg', '', 1, '2026-01-21 09:41:38'),
(2, 4, '1768988498_4_1_download2.jpg', '', 2, '2026-01-21 09:41:38'),
(3, 4, '1768988498_4_2_download.jpg', '', 3, '2026-01-21 09:41:38'),
(4, 4, '1768988498_4_3_images.jpg', '', 4, '2026-01-21 09:41:38'),
(5, 2, '2_screenshot_1768989231_6970a22f2637e.jpg', '', 1, '2026-01-21 09:53:51'),
(6, 2, '2_screenshot_1768989231_6970a22f27f8b.jpg', '', 2, '2026-01-21 09:53:51'),
(7, 1, '1_screenshot_1768989310_6970a27e3f5f3.jpg', '', 1, '2026-01-21 09:55:10'),
(8, 1, '1_screenshot_1768989310_6970a27e415a9.jpg', '', 2, '2026-01-21 09:55:10'),
(9, 5, '1768998258_5_0_3_Sidekick_Tall_Mos_Eisley.jpg', '', 1, '2026-01-21 12:24:18'),
(10, 5, '1768998258_5_1_Lightsaber-Bread.webp', '', 2, '2026-01-21 12:24:18'),
(11, 5, '1768998258_5_2_ss_0643bd5278bbdc315b934368f871892cc696b52d.1920x1080.jpg', '', 3, '2026-01-21 12:24:18'),
(12, 5, '1768998258_5_3_XL_hero_Gameplay_trailer_desktop.jpg', '', 4, '2026-01-21 12:24:18'),
(13, 6, '1768998488_6_0_06ZixgnyWuWSVyvwk39OTI9-1..v1569469921.jpg', '', 1, '2026-01-21 12:28:08'),
(14, 6, '1768998488_6_1_images4.jpg', '', 2, '2026-01-21 12:28:08'),
(15, 6, '1768998488_6_2_qf91enynlq3hlgtkmbon.avif', '', 3, '2026-01-21 12:28:08'),
(16, 7, '1768998645_7_0_images6.jpg', '', 1, '2026-01-21 12:30:45'),
(17, 7, '1768998645_7_1_images7.jpg', '', 2, '2026-01-21 12:30:45'),
(18, 8, '1768998874_8_0_images8.jpg', '', 1, '2026-01-21 12:34:34'),
(19, 8, '1768998874_8_1_images9.jpg', '', 2, '2026-01-21 12:34:34'),
(20, 8, '1768998874_8_2_ss_f8d0d6fbb4c9bb1b9a6ecf44574f8a89244ba61e.jpg', '', 3, '2026-01-21 12:34:34'),
(21, 9, '1769705069_9_0_images1.jpg', '', 1, '2026-01-29 16:44:29'),
(22, 9, '1769705069_9_1_images10.jpg', '', 2, '2026-01-29 16:44:29'),
(23, 9, '1769705069_9_2_images.jpg', '', 3, '2026-01-29 16:44:29');

-- --------------------------------------------------------

--
-- Struktur dari tabel `library`
--

CREATE TABLE `library` (
  `id_library` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_game` int(11) NOT NULL,
  `purchased_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `library`
--

INSERT INTO `library` (`id_library`, `id_user`, `id_game`, `purchased_at`) VALUES
(5, 5, 4, '2026-01-21 18:05:22'),
(6, 5, 8, '2026-01-21 19:44:57'),
(9, 5, 7, '2026-01-21 20:35:43'),
(10, 5, 6, '2026-01-21 21:43:25'),
(11, 5, 9, '2026-01-30 00:26:32'),
(14, 5, 1, '2026-01-30 01:11:17');

-- --------------------------------------------------------

--
-- Struktur dari tabel `transactions`
--

CREATE TABLE `transactions` (
  `id_transaction` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `total_price` int(11) NOT NULL,
  `transaction_date` datetime DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'completed',
  `payment_method` varchar(50) DEFAULT 'midnight_wallet'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transactions`
--

INSERT INTO `transactions` (`id_transaction`, `id_user`, `total_price`, `transaction_date`, `status`, `payment_method`) VALUES
(14, 5, 479000, '2026-01-21 18:05:22', 'completed', 'midnight_wallet'),
(15, 5, 34999, '2026-01-21 19:44:57', 'completed', 'midnight_wallet'),
(19, 5, 107999, '2026-01-21 20:35:43', 'completed', 'midnight_wallet'),
(20, 5, 0, '2026-01-21 21:43:25', 'completed', 'midnight_wallet'),
(21, 5, 463200, '2026-01-30 00:26:31', 'completed', 'midnight_wallet'),
(28, 5, 799000, '2026-01-29 19:11:17', 'completed', 'midnight_wallet');

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaction_details`
--

CREATE TABLE `transaction_details` (
  `id_detail` int(11) NOT NULL,
  `id_transaction` int(11) NOT NULL,
  `id_game` int(11) NOT NULL,
  `price` int(11) NOT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transaction_details`
--

INSERT INTO `transaction_details` (`id_detail`, `id_transaction`, `id_game`, `price`, `transaction_date`) VALUES
(3, 14, 4, 479000, '2026-01-21 11:05:22'),
(4, 15, 8, 34999, '2026-01-21 12:44:57'),
(7, 19, 7, 107999, '2026-01-21 13:35:43'),
(8, 20, 6, 0, '2026-01-21 14:43:25'),
(9, 21, 9, 463200, '2026-01-29 17:26:31'),
(16, 28, 1, 799000, '2026-01-29 18:11:17');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL,
  `total_spent` decimal(10,2) DEFAULT 0.00,
  `wallet_balance` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id_user`, `username`, `password`, `role`, `total_spent`, `wallet_balance`) VALUES
(1, 'admin', '0192023a7bbd73250516f069df18b500', 'admin', 0.00, 0),
(5, 'Pickyqi', '482c811da5d5b4bc6d497ffa98491e38', 'user', 0.00, 98402000);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id_cart`),
  ADD KEY `id_game` (`id_game`),
  ADD KEY `idx_cart_user` (`id_user`),
  ADD KEY `idx_cart_status` (`status`),
  ADD KEY `idx_cart_user_status` (`id_user`,`status`);

--
-- Indeks untuk tabel `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id_game`);

--
-- Indeks untuk tabel `game_screenshots`
--
ALTER TABLE `game_screenshots`
  ADD PRIMARY KEY (`id_screenshot`),
  ADD KEY `idx_screenshots_game` (`id_game`),
  ADD KEY `idx_screenshots_order` (`id_game`,`display_order`);

--
-- Indeks untuk tabel `library`
--
ALTER TABLE `library`
  ADD PRIMARY KEY (`id_library`),
  ADD UNIQUE KEY `unique_user_game` (`id_user`,`id_game`),
  ADD KEY `id_game` (`id_game`);

--
-- Indeks untuk tabel `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id_transaction`),
  ADD KEY `idx_transaction_date` (`transaction_date`),
  ADD KEY `idx_transaction_user` (`id_user`),
  ADD KEY `idx_transaction_status` (`status`);

--
-- Indeks untuk tabel `transaction_details`
--
ALTER TABLE `transaction_details`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_transaction` (`id_transaction`),
  ADD KEY `id_game` (`id_game`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `carts`
--
ALTER TABLE `carts`
  MODIFY `id_cart` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `games`
--
ALTER TABLE `games`
  MODIFY `id_game` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `game_screenshots`
--
ALTER TABLE `game_screenshots`
  MODIFY `id_screenshot` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT untuk tabel `library`
--
ALTER TABLE `library`
  MODIFY `id_library` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT untuk tabel `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id_transaction` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT untuk tabel `transaction_details`
--
ALTER TABLE `transaction_details`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `carts_ibfk_2` FOREIGN KEY (`id_game`) REFERENCES `games` (`id_game`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `game_screenshots`
--
ALTER TABLE `game_screenshots`
  ADD CONSTRAINT `game_screenshots_ibfk_1` FOREIGN KEY (`id_game`) REFERENCES `games` (`id_game`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `library`
--
ALTER TABLE `library`
  ADD CONSTRAINT `library_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`),
  ADD CONSTRAINT `library_ibfk_2` FOREIGN KEY (`id_game`) REFERENCES `games` (`id_game`);

--
-- Ketidakleluasaan untuk tabel `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`);

--
-- Ketidakleluasaan untuk tabel `transaction_details`
--
ALTER TABLE `transaction_details`
  ADD CONSTRAINT `transaction_details_ibfk_1` FOREIGN KEY (`id_transaction`) REFERENCES `transactions` (`id_transaction`),
  ADD CONSTRAINT `transaction_details_ibfk_2` FOREIGN KEY (`id_game`) REFERENCES `games` (`id_game`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
