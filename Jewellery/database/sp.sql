CREATE TABLE `product_details` (
  `id` int(11) NOT NULL,
  `product_id` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `material` varchar(100) DEFAULT NULL,
  `design` varchar(255) DEFAULT NULL,
  `stone` varchar(100) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `product_details`
--

INSERT INTO `product_details` (`id`, `product_id`, `description`, `material`, `design`, `stone`, `color`, `brand`) VALUES
(1, 'R001', 'A refined sterling silver ring featuring a shimmering teardrop Opal stone, radiating elegance and feminine charm.', 'Sterling Silver', 'Teardrop shape', 'Synthetic Opal', 'Silver', 'ThirtySix Jewellery'),
(2, 'R002', 'A delicate floral-inspired ring with sparkling crystal accents, perfect for a soft feminine look.', 'Sterling Silver', 'Floral design', 'Crystal', 'Black', 'ThirtySix Jewellery'),
(3, 'R003', 'A refined sterling silver ring featuring a shimmering teardrop Opal stone.', 'Sterling Silver', 'Teardrop shape', 'Synthetic Opal', 'Silver', 'ThirtySix Jewellery'),
(4, 'R004', 'A luxurious platinum ring featuring a clover charm symbolizing luck.', 'Platinum', 'Clover charm', 'None', 'Silver', 'ThirtySix Jewellery'),
(5, 'R005', 'An elegant moissanite ring with paisley-inspired patterns.', 'White Gold', 'Paisley pattern', 'Moissanite', 'Silver', 'ThirtySix Jewellery'),
(6, 'R006', 'A trendy crown stack ring perfect for layering.', 'Sterling Silver', 'Stackable crown', 'None', 'Gold', 'ThirtySix Jewellery'),
(7, 'R007', 'A bold masculine ring with a modern design.', 'Stainless Steel', 'Minimalist', 'None', 'Silver', 'ThirtySix Jewellery'),
(8, 'R008', 'A refined silver ring for a sophisticated look.', 'Sterling Silver', 'Polished design', 'None', 'Silver', 'ThirtySix Jewellery'),
(9, 'R009', 'A unisex ring with fluid curves representing balance.', 'Sterling Silver', 'Flowing curve', 'None', 'Silver', 'ThirtySix Jewellery'),
(10, 'R010', 'A stylish ring featuring a striking onyx centerpiece.', 'Stainless Steel', 'Edge-cut', 'Onyx', 'Black', 'ThirtySix Jewellery');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `product_details`
--
ALTER TABLE `product_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `product_details`
--
ALTER TABLE `product_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
ALTER TABLE `product_details`
  ADD CONSTRAINT `product_details_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
REATE TABLE `products` (
  `id` varchar(50) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `price` decimal(15,2) DEFAULT 0.00,
  `image` varchar(255) DEFAULT NULL,
  `cost_price` decimal(15,2) DEFAULT 0.00,
  `profit_percent` int(11) DEFAULT 0,
  `stock` int(11) DEFAULT 0,
  `category` varchar(100) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `image`, `cost_price`, `profit_percent`, `stock`, `category`, `gender`) VALUES
('R001', 'Kane Moissanite Ring', 1326.00, 'R001.jpg', 1020.00, 30, 10, 'Ring', 'Male'),
('R002', 'Winston Anchor Ring', 1708.00, 'R002.jpg', 1220.00, 40, 5, 'Ring', 'Male'),
('R003', 'Ula Opal Teardrop Ring', 49.38, 'R003.jpg', 39.50, 25, 15, 'Ring', 'Female'),
('R004', 'Platinum Clover Charm Ring', 1633.50, 'R004.jpg', 1210.00, 35, 8, 'Ring', 'Female'),
('R005', 'Paisley Moissanite Ring', 1176.00, 'R005.jpg', 980.00, 20, 12, 'Ring', 'Female'),
('R006', 'Niche Crown Stack Ring', 120.00, 'R006.jpg', 60.00, 100, 10, 'Ring', 'Female'),
('R007', 'The Zenith Ring', 130.00, 'R007.jpg', 70.00, 85, 15, 'Ring', 'Male'),
('R008', 'Silver Eminence Ring', 150.00, 'R008.jpg', 90.00, 67, 8, 'Ring', 'Male'),
('R009', 'Elysian Flow', 200.00, 'R009.jpg', 120.00, 67, 5, 'Ring', 'Unisex'),
('R010', 'Onyx Edge', 95.00, 'R010.jpg', 50.00, 90, 12, 'Ring', 'Unisex');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);
CREATE TABLE `cart` (
  `product_id` varchar(50) NOT NULL,
  `quantity` int(11) DEFAULT NULL,
  `size` varchar(10) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
ALTER TABLE `cart`
  ADD PRIMARY KEY (`product_id`);
COMMIT;