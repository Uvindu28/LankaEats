-- phpMyAdmin-style SQL Dump
-- ------------------------------------------------------------
-- Project : LankaEats – Sri Lankan Digital Recipe Book
-- Course  : ICT 2209 – Web Technologies (Mini Project)
-- Host    : localhost (XAMPP)
-- Server  : MariaDB 10.4 / MySQL 5.7+ compatible
-- Charset : utf8mb4
--
-- HOW TO IMPORT: phpMyAdmin -> Import -> choose this file -> Go.
-- The script creates the `lanka_eats` database itself.
--
-- Demo logins (passwords are stored ONLY as password_hash() values):
--   demo           / Demo@123
--   kithul_kitchen / Kithul@123
-- ------------------------------------------------------------

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+05:30";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lanka_eats`
--
CREATE DATABASE IF NOT EXISTS `lanka_eats` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `lanka_eats`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `recipes`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------

--
-- Table structure for table `users`
-- (exactly as required by the guide: id, username, email, password (hashed), created_at)
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'password_hash() output - never plain text',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `created_at`) VALUES
(1, 'demo', 'demo@lankaeats.lk', '$2y$10$qBGR7kgXnvJzot22ldGV6ukLFq/9RTMptmeRrtRyE0LyyiDcXugTi', '2026-08-01 09:15:00'),
(2, 'kithul_kitchen', 'kithul@lankaeats.lk', '$2y$10$TWEVU4KxiAnzt7N9Rhc6Vum1Kum6JkLLDnq8kO2fKari9.ME1U3Ze', '2026-08-03 18:40:00');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL COMMENT 'URL-friendly name, also used for icon file names',
  `description` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_name` (`name`),
  UNIQUE KEY `uq_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`) VALUES
(1, 'Rice & Curry', 'rice-curry', 'The everyday heart of the island: a mound of rice ringed by curries, mallums and sambols.'),
(2, 'Street Food', 'street-food', 'Loud, fast and full of flavour, from the clang of kottu blades to roadside vadai carts.'),
(3, 'Breakfast', 'breakfast', 'Hoppers, pittu and roti that start the morning, usually with a fiery sambol on the side.'),
(4, 'Sweets', 'sweets', 'Avurudu favourites and festive treats made with kithul treacle, coconut and rice flour.'),
(5, 'Short Eats', 'short-eats', 'Bakery-counter bites: buns, rolls and roti for tea time or eating on the go.'),
(6, 'Drinks', 'drinks', 'Cooling, comforting and colourful sips, from herbal porridge to rose-scented faluda.');

-- --------------------------------------------------------

--
-- Table structure for table `recipes`
--

CREATE TABLE `recipes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` varchar(500) NOT NULL,
  `ingredients` text NOT NULL COMMENT 'one ingredient per line',
  `instructions` text NOT NULL COMMENT 'one step per line',
  `prep_time` int(11) NOT NULL COMMENT 'minutes',
  `difficulty` enum('Easy','Medium','Hard') NOT NULL DEFAULT 'Easy',
  `spice_level` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1 (mild) to 5 (very hot)',
  `is_vegetarian` tinyint(1) NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL COMMENT 'file name inside images/uploads/',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_recipes_user` (`user_id`),
  KEY `idx_recipes_category` (`category_id`),
  KEY `idx_recipes_featured` (`is_featured`),
  CONSTRAINT `fk_recipes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_recipes_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_recipes_spice` CHECK (`spice_level` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recipes`
--

INSERT INTO `recipes` (`id`, `user_id`, `category_id`, `title`, `description`, `ingredients`, `instructions`, `prep_time`, `difficulty`, `spice_level`, `is_vegetarian`, `image`, `is_featured`, `created_at`) VALUES
(1, 1, 1, 'Parippu (Red Lentil Curry)', 'Silky red lentils simmered with turmeric and finished with a sizzling tempering of mustard seeds, curry leaves and onion. The gentle, golden curry that turns up on almost every Sri Lankan rice plate.', '1 cup red lentils (masoor dhal), rinsed\n2 cups water\n1/2 teaspoon turmeric powder\n1 green chilli, slit\n1 small onion, sliced\n2 cloves garlic, sliced\n1 cup thick coconut milk\n1 teaspoon mustard seeds\n1 sprig curry leaves\n2 dried red chillies, broken\n1 tablespoon coconut oil\nSalt to taste', 'Rinse the lentils until the water runs almost clear, then add them to a pot with the water, turmeric, green chilli and half of the onion.\nSimmer on medium heat for about 12 minutes, stirring now and then, until the lentils turn soft and start to break down.\nPour in the coconut milk and salt, lower the heat and let it bubble gently for 5 more minutes until creamy.\nIn a small pan heat the coconut oil, add the mustard seeds and wait until they start to pop.\nAdd the remaining onion, garlic, curry leaves and dried chillies and fry until the onion is golden.\nTip the sizzling tempering over the lentils, stir once and serve warm with rice.', 30, 'Easy', 2, 1, NULL, 1, '2026-08-02 10:00:00'),
(2, 1, 1, 'Kukul Mas Curry (Village Chicken Curry)', 'A dark, deeply roasted chicken curry built on toasted curry powder, cinnamon and pandan leaf. Slow simmering lets the spices soak right into the meat for a rich Sunday-lunch gravy.', '750 g chicken, cut into curry pieces\n2 tablespoons roasted Sri Lankan curry powder\n1 teaspoon chilli powder\n1/2 teaspoon turmeric powder\n1 large onion, finely chopped\n4 cloves garlic and a thumb of ginger, crushed\n1 stick cinnamon\n4 cardamom pods, bruised\n1 sprig curry leaves and a small piece of pandan (rampe) leaf\n1 tomato, chopped\n1 cup coconut milk\n2 tablespoons oil\nSalt and a squeeze of lime', 'Rub the chicken with the curry powder, chilli powder, turmeric and salt and leave it to marinate for at least 20 minutes.\nHeat the oil in a heavy pot and fry the cinnamon, cardamom, curry leaves and pandan until fragrant.\nAdd the onion, garlic and ginger and cook until soft and lightly browned.\nAdd the chicken and stir over high heat for 5 minutes to seal in the spices.\nAdd the tomato and half a cup of water, cover and simmer for 25 minutes.\nPour in the coconut milk and cook uncovered until the gravy thickens and clings to the chicken.\nFinish with a squeeze of lime, check the salt and rest for 5 minutes before serving.', 60, 'Medium', 4, 0, NULL, 1, '2026-08-04 12:30:00'),
(3, 1, 1, 'Polos Curry (Young Jackfruit)', 'Tender young jackfruit cooked until it is almost meaty, soaking up roasted spices and thick coconut milk. Often called the vegetarian chicken curry, and a favourite at almsgivings.', '500 g young jackfruit (polos), peeled and cubed\n1 teaspoon turmeric powder\n1 1/2 tablespoons roasted curry powder\n1 teaspoon chilli flakes\n1 small red onion, sliced\n3 cloves garlic, chopped\n1 piece goraka (garcinia) or 1 teaspoon tamarind paste\n1 stick cinnamon\n1 sprig curry leaves\n1 1/2 cups coconut milk\n1 tablespoon mustard seeds, ground with a little vinegar\nSalt to taste', 'Boil the jackfruit cubes in salted water with the turmeric for 15 minutes, then drain.\nCombine the jackfruit with the curry powder, chilli flakes, onion, garlic, goraka, cinnamon and curry leaves in a clay pot or heavy pan.\nAdd one cup of the coconut milk and simmer on low heat for 30 minutes, stirring gently so the pieces keep their shape.\nStir in the mustard paste and the remaining coconut milk and cook for 10 more minutes until the gravy is thick and dark.\nLet the curry rest before serving; it tastes even better the next day.', 75, 'Medium', 3, 1, NULL, 0, '2026-08-06 09:45:00'),
(4, 1, 1, 'Pol Sambol (Coconut Relish)', 'Freshly scraped coconut rubbed with chilli, shallots, salt and lime until it glows orange. Bright, sharp and fiery, it wakes up everything from hoppers to plain bread. This version skips Maldive fish, so it stays vegetarian.', '2 cups freshly scraped coconut\n1 to 2 teaspoons chilli flakes or chilli powder\n4 small red onions (shallots), finely chopped\n1 green chilli, chopped (optional)\nJuice of half a lime\n1/2 teaspoon salt', 'In a mortar, or a bowl with the back of a spoon, crush the red onion, chilli flakes and salt into a rough paste.\nAdd the scraped coconut and rub everything together with your fingers until the coconut turns an even orange-red.\nSqueeze in the lime juice, mix again and taste; adjust the salt, lime and chilli to your liking.\nServe straight away while the coconut is still fresh and fluffy.', 10, 'Easy', 4, 1, NULL, 0, '2026-08-07 07:20:00'),
(5, 1, 2, 'Chicken Kottu Roti', 'Shredded godamba roti tossed on a hot griddle with vegetables, egg, chicken and curry gravy, chopped to the famous clanging rhythm of two metal blades. The island favourite for a late-night feed.', '4 godamba or paratha roti, cut into thin strips\n1 cup cooked chicken, shredded\n1/2 cup chicken curry gravy\n2 eggs\n1 leek, thinly sliced\n1 carrot, cut into thin sticks\n1 onion, sliced\n2 green chillies, chopped\n1 sprig curry leaves\n1/2 teaspoon chilli flakes\n2 tablespoons oil\nSalt and pepper', 'Heat the oil on a large flat pan or griddle and fry the onion, green chilli and curry leaves until soft.\nAdd the leek and carrot and stir-fry for 2 minutes so they stay slightly crunchy.\nPush the vegetables to one side, crack in the eggs and scramble them.\nAdd the chicken, roti strips, chilli flakes, salt and pepper and toss everything together.\nPour in the curry gravy and chop through the mix with two flat metal spatulas until the roti is in small pieces and well coated.\nServe hot with extra gravy on the side.', 40, 'Medium', 3, 0, NULL, 1, '2026-08-09 20:10:00'),
(6, 2, 2, 'Isso Vadai (Prawn Lentil Fritters)', 'Crunchy split-lentil patties crowned with a whole prawn, sold warm from glass-fronted carts along the seafront. Best eaten facing the waves with a spoonful of onion and chilli sambol.', '1 cup split chickpeas (kadala parippu), soaked for 3 hours\n1 small onion, finely chopped\n2 green chillies, finely chopped\n1 sprig curry leaves, chopped\n1/2 teaspoon fennel seeds\n1/2 teaspoon chilli flakes\n12 small prawns, cleaned with tails on\n1/4 teaspoon turmeric powder\nSalt to taste\nOil for deep frying', 'Drain the soaked lentils well and grind them coarsely, keeping some texture; do not add water.\nMix in the onion, green chillies, curry leaves, fennel seeds, chilli flakes and salt.\nToss the prawns with the turmeric and a pinch of salt.\nShape the lentil mixture into small flat discs and press one prawn firmly on top of each.\nDeep fry in hot oil for 3 to 4 minutes, turning once, until deep golden and crisp.\nDrain on paper and serve warm.', 50, 'Medium', 3, 0, NULL, 0, '2026-08-11 16:00:00'),
(7, 1, 3, 'Appa (Plain Hoppers)', 'Bowl-shaped pancakes of fermented rice flour and coconut milk with lacy, crisp edges and a soft, spongy centre. Crack an egg into the middle for an egg hopper, or eat them plain with lunu miris.', '2 cups rice flour\n1 teaspoon instant yeast\n1 teaspoon sugar\n1 1/2 cups warm water\n1 1/2 cups thick coconut milk\n1/2 teaspoon salt\n1/4 teaspoon baking soda\nA little oil for the pan', 'Stir the yeast and sugar into half a cup of the warm water and leave for 10 minutes until frothy.\nMix the rice flour with the yeast mixture and the rest of the water to form a thick batter, then cover and leave overnight (about 8 hours) to ferment.\nIn the morning, stir in the coconut milk, salt and baking soda until the batter is as thin as pouring cream.\nHeat a small hopper pan (appa thachchiya), wipe it with a little oil and pour in a ladle of batter.\nQuickly swirl the pan in a circle so the batter coats the sides thinly, then cover with a lid.\nCook on medium-low heat for 2 to 3 minutes until the edges are brown and crisp and the centre is set.\nEase the hopper out with a thin spatula and repeat with the remaining batter.', 45, 'Hard', 1, 1, NULL, 1, '2026-08-12 06:30:00'),
(8, 1, 3, 'Pittu with Coconut Milk', 'Steamed logs of roasted rice flour and freshly grated coconut, light and crumbly. Pittu is broken up on the plate, soaked with coconut milk and eaten with a spoonful of spicy curry.', '2 cups roasted red rice flour\n1 cup freshly grated coconut\n1/2 teaspoon salt\nAbout 3/4 cup warm water\nThick coconut milk to serve', 'Mix the flour and salt in a wide bowl.\nSprinkle in the warm water a little at a time, rubbing it through with your fingertips until the flour forms small crumbs that hold together when pressed.\nGently mix in the grated coconut so the crumbs stay loose.\nFill a pittu bamboo or steamer tube, alternating layers of coconut and crumb mixture, without packing it down.\nSteam for 10 to 12 minutes until the pittu smells cooked and feels firm.\nPush the pittu out onto a plate and serve warm with coconut milk and a curry of your choice.', 35, 'Medium', 1, 1, NULL, 0, '2026-08-14 07:00:00'),
(9, 1, 3, 'Pol Roti (Coconut Flatbread)', 'Rustic flatbreads of flour and grated coconut, flecked with onion, green chilli and curry leaves. Cooked on a dry pan until speckled brown and served with lunu miris for a quick village breakfast.', '2 cups all-purpose flour\n1 1/2 cups freshly grated coconut\n1 small onion, finely chopped\n1 green chilli, finely chopped\n6 curry leaves, finely chopped\n1/2 teaspoon salt\nAbout 1/2 cup water\n1 teaspoon coconut oil', 'Combine the flour, coconut, onion, green chilli, curry leaves and salt in a bowl.\nAdd the water gradually and knead into a soft dough that no longer sticks to your hands.\nDivide into 6 balls and flatten each one into a round about 1 cm thick using oiled palms.\nCook on a hot, dry pan for 3 to 4 minutes per side until brown spots appear.\nServe warm with lunu miris or a curry.', 25, 'Easy', 2, 1, NULL, 0, '2026-08-15 07:45:00'),
(10, 1, 4, 'Kokis (Crispy Rosettes)', 'Crisp, flower-shaped rosettes made by dipping a hot brass mould into rice flour and coconut milk batter. No Sinhala and Tamil New Year table feels complete without a tin of these.', '2 cups rice flour\n1 1/2 cups thick coconut milk\n1 tablespoon sugar\n1/2 teaspoon turmeric powder (for colour)\n1/2 teaspoon salt\nA pinch of baking soda\nCoconut oil for deep frying', 'Whisk the rice flour, sugar, turmeric, salt and baking soda together, then gradually add the coconut milk to make a smooth batter like thin pancake batter.\nRest the batter for 30 minutes and strain it if there are any lumps.\nHeat the oil in a deep pan and warm the kokis mould in the oil for a few minutes.\nLift the mould, let the extra oil drip off and dip it into the batter so it covers the sides but not the top.\nLower the coated mould back into the hot oil; after a few seconds shake it gently so the kokis slides off.\nFry until crisp and pale golden, then lift out and drain upright on paper.\nReheat the mould in the oil before each dip, and store the cooled kokis in an airtight tin.', 60, 'Hard', 1, 1, NULL, 1, '2026-08-17 15:00:00'),
(11, 2, 4, 'Konda Kavum (Oil Cakes)', 'Sweet little cakes of rice flour and kithul treacle, fried until a crown-like knot puffs up in the centre. Sticky, glossy and golden, they are the pride of every New Year sweet platter.', '2 cups raw white rice flour\n1 cup kithul treacle\n1/2 cup sugar\n1/4 teaspoon salt\n1/4 cup water\nCoconut oil for deep frying', 'Boil the treacle, sugar, water and salt together until it reaches soft-ball stage (a drop in cold water forms a soft ball).\nPour the hot syrup into the rice flour a little at a time, mixing to a thick, glossy dough.\nCover and rest the dough overnight so it matures.\nThe next day, loosen the dough with a spoonful of water if needed; it should drop slowly from a spoon.\nHeat the oil and drop in a spoonful of dough; as it rises, spoon hot oil over the centre and nudge the middle with a skewer so the knot forms.\nFry until deep golden, lift out and press lightly between two spoons to drain off the extra oil.', 90, 'Hard', 1, 1, NULL, 0, '2026-08-18 11:20:00'),
(12, 1, 4, 'Watalappan (Jaggery Custard)', 'A silky steamed custard of coconut milk, eggs and dark jaggery, spiced with cardamom, nutmeg and a hint of clove. Rooted in the island Malay community and loved at weddings and Ramazan feasts.', '250 g kithul jaggery, grated\n1 cup thick coconut milk\n5 eggs\n1/4 cup water\n1/2 teaspoon ground cardamom\nA pinch of ground nutmeg\nA pinch of ground cloves\nA handful of cashew nuts, roughly chopped', 'Melt the jaggery with the water over low heat, stirring until dissolved, then strain and let it cool.\nWhisk the eggs just enough to break them up without making them foamy.\nStir in the cooled jaggery syrup, coconut milk, cardamom, nutmeg and cloves, then strain the mixture into a heatproof dish.\nScatter the cashews over the top and cover the dish with foil.\nSteam for about 40 minutes until the centre is just set and a knife comes out clean.\nCool completely, then chill for a few hours before serving.', 70, 'Medium', 1, 1, NULL, 0, '2026-08-20 14:10:00'),
(13, 1, 5, 'Fish Buns (Maalu Paan)', 'Soft, slightly sweet bread rolls folded around a spicy filling of fish, potato and onion. The tea-time favourite that every bakery three-wheeler announces with its jingling tune.', '3 cups all-purpose flour\n1 teaspoon instant yeast\n2 tablespoons sugar\n1/2 teaspoon salt\n1 cup warm milk\n2 tablespoons butter\n1 can (425 g) mackerel, drained and flaked\n2 potatoes, boiled and mashed\n1 onion, finely chopped\n2 green chillies, chopped\n1 teaspoon chilli powder\n1 sprig curry leaves\n1 egg, beaten, for glazing', 'Mix the flour, yeast, sugar and salt, add the warm milk and butter and knead for 8 minutes into a smooth dough.\nCover and leave to rise for about an hour until doubled in size.\nMeanwhile fry the onion, green chillies and curry leaves until soft, then add the fish, chilli powder and salt and cook for 5 minutes.\nStir in the mashed potato and let the filling cool completely.\nDivide the dough into 10 pieces, flatten each one, add a spoonful of filling and fold into a triangle, pinching the edges to seal.\nRest on a tray for 20 minutes, brush with beaten egg and bake at 190 C for 18 to 20 minutes until golden.', 120, 'Medium', 3, 0, NULL, 1, '2026-08-22 16:30:00'),
(14, 1, 5, 'Vegetable Roti (Triangle Roti)', 'Paper-thin dough folded into neat triangles around a peppery potato and leek filling, then crisped on a griddle. A cheap, cheerful short eat found in every roadside tea boutique.', '2 cups all-purpose flour\n1/2 teaspoon salt\nAbout 3/4 cup water\n3 tablespoons oil\n3 potatoes, boiled and diced small\n1 leek, finely sliced\n1 carrot, grated\n1 onion, chopped\n2 green chillies, chopped\n1 sprig curry leaves\n1 teaspoon curry powder\n1/2 teaspoon black pepper', 'Knead the flour, salt and water into a soft dough, coat it with oil and rest it for at least 30 minutes.\nFry the onion, green chillies and curry leaves, then add the leek, carrot, curry powder and pepper and cook for 3 minutes.\nMix in the potato, season with salt and let the filling cool.\nDivide the dough into balls and stretch each one on an oiled surface until very thin.\nPlace a spoonful of filling in the centre and fold the sides over to make a neat triangle.\nCook on a hot oiled griddle for 2 to 3 minutes per side until crisp and golden.', 50, 'Medium', 3, 1, NULL, 0, '2026-08-24 17:15:00'),
(15, 1, 6, 'Faluda (Rose Milk Float)', 'A pastel-pink glass of chilled milk and rose syrup layered with wobbly jelly and swollen basil seeds, crowned with a scoop of ice cream. A cooling treat for the hottest afternoons in Pettah.', '2 cups cold full-cream milk\n4 tablespoons rose syrup\n1 tablespoon basil seeds (kasa kasa)\n1/2 cup water\n1/2 cup red or green jelly, set and cubed\n2 scoops vanilla ice cream\nCrushed ice', 'Soak the basil seeds in the water for 15 minutes until each seed is wrapped in a soft jelly coat.\nStir 2 tablespoons of the rose syrup into the cold milk.\nPour a tablespoon of rose syrup into the bottom of each tall glass, then add jelly cubes and a spoonful of basil seeds.\nAdd some crushed ice and slowly pour in the pink milk.\nTop each glass with a scoop of ice cream and serve with a long spoon and a straw.', 20, 'Easy', 1, 1, NULL, 0, '2026-08-26 15:40:00'),
(16, 2, 6, 'Kola Kenda (Herbal Porridge)', 'A bright green, nourishing porridge of red rice, coconut milk and fresh gotukola leaves, sipped warm with a piece of jaggery on the side. A healthy morning drink sold at village markets.', '1/2 cup red rice, washed\n4 cups water\n1 cup gotukola (Asiatic pennywort) leaves, packed\n1 cup thick coconut milk\n2 cloves garlic\n1/2 teaspoon salt\nJaggery to serve', 'Simmer the red rice with the garlic and 3 cups of the water for about 25 minutes until very soft.\nBlend the gotukola leaves with the remaining cup of water and strain to get a smooth green juice.\nStir the coconut milk and salt into the rice and bring it back to a gentle simmer.\nTurn off the heat and immediately stir in the green juice so it keeps its bright colour.\nServe warm in cups with a small piece of jaggery on the side.', 30, 'Easy', 1, 1, NULL, 0, '2026-08-28 06:50:00');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
-- (contact form submissions: id, name, email, message, created_at + subject)
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `name`, `email`, `subject`, `message`, `created_at`) VALUES
(1, 'Nimali Perera', 'nimali@example.com', 'Recipe request', 'Could you add a recipe for string hoppers (idiyappam) with kiri hodi? My family would love to try it.', '2026-09-01 19:05:00');

--
-- AUTO_INCREMENT values for dumped tables
--
ALTER TABLE `users` AUTO_INCREMENT=3;
ALTER TABLE `categories` AUTO_INCREMENT=7;
ALTER TABLE `recipes` AUTO_INCREMENT=17;
ALTER TABLE `messages` AUTO_INCREMENT=2;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
