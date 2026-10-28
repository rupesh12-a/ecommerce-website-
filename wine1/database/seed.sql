-- Vino e Casa — sample data
SET NAMES utf8mb4;
USE wine_shop;

INSERT INTO categories (name, slug, description) VALUES
('Red Wine', 'red-wine', 'Bold, structured reds from classic and New World regions.'),
('White Wine', 'white-cellar', 'Crisp, aromatic whites — from mineral Chablis to zesty Sauvignon Blanc.'),
('Rosé', 'rose', 'Pale Provence styles and juicy, food-friendly rosés.'),
('Sparkling & Champagne', 'sparkling', 'Champagne, Prosecco and celebratory bubbles.'),
('Dessert & Fortified', 'dessert', 'Late-harvest treasures, Ports and fortified classics.');

INSERT INTO products (category_id, name, slug, description, price, sale_price, stock, vintage, region, grape, alcohol, image, featured) VALUES
(1,'Château Margaux Reserve 2018','chateau-margaux-reserve-2018','A benchmark Bordeaux blend with cassis, violet and cedar. Silky tannins lead to a long, mineral finish. Decant 60 minutes before serving at 17°C.',8499.00,NULL,12,2018,'Bordeaux, France','Cabernet Sauvignon',13.5,'assets/img/wine-01.svg',1),
(1,'Barolo Riserva Casa Nova 2017','barolo-riserva-casa-nova-2017','Nebbiolo at its most expressive — rose petal, tar, dried cherry and spice. Ages gracefully for another decade. Pair with truffle risotto or aged cheese.',6999.00,NULL,8,2017,'Piedmont, Italy','Nebbiolo',14.5,'assets/img/wine-02.svg',1),
(1,'Napa Valley Cabernet 2019','napa-valley-cabernet-2019','Ripe blackberry, mocha and toasted oak from hillside Napa fruit. Full-bodied with polished tannins — a crowd favourite for grilled steaks.',6999.00,5499.00,20,2019,'California, USA','Cabernet Sauvignon',14.0,'assets/img/wine-03.svg',1),
(1,'Malbec Reserva 2020','malbec-reserva-2020','High-altitude Mendoza malbec with plum, violet and a hint of vanilla. Soft, generous and dangerously easy to drink.',3299.00,NULL,35,2020,'Mendoza, Argentina','Malbec',13.5,'assets/img/wine-04.svg',0),
(1,'Shiraz Black Ridge 2021','shiraz-black-ridge-2021','Barossa Shiraz at its boldest: blackberry compote, black pepper, cocoa and a smoky finish. Decant and serve with barbecue.',2799.00,NULL,42,2021,'Barossa Valley, Australia','Shiraz',14.5,'assets/img/wine-05.svg',0),
(1,'Tempranillo Crianza 2019','tempranillo-crianza-2019','Classic Rioja Crianza aged in American oak — red fruit, dill, leather. Versatile with tapas, roast chicken or manchego.',2499.00,NULL,50,2019,'Rioja, Spain','Tempranillo',13.5,'assets/img/wine-06.svg',0),
(2,'Chablis Premier Cru 2021','chablis-premier-cru-2021','Chardonnay from Kimmeridgian limestone — lemon zest, white flower, oyster-shell salinity. Serve well chilled with seafood.',4999.00,NULL,15,2021,'Burgundy, France','Chardonnay',12.5,'assets/img/wine-07.svg',1),
(2,'Marlborough Sauvignon Blanc 2022','marlborough-sauvignon-blanc-2022','Explosive passionfruit, grapefruit and fresh-cut grass. Electric acidity that screams for goat cheese or a warm afternoon.',2899.00,NULL,60,2022,'Marlborough, New Zealand','Sauvignon Blanc',13.0,'assets/img/wine-08.svg',1),
(2,'Riesling Kabinett 2021','riesling-kabinett-2021','Feinherb Mosel riesling — green apple, lime and slate with just a kiss of sweetness balanced by razor acidity.',3499.00,NULL,25,2021,'Mosel, Germany','Riesling',8.5,'assets/img/wine-09.svg',0),
(2,'Pinot Grigio DOC 2022','pinot-grigio-doc-2022','Crisp Veneto pinot grigio with pear, citrus and almond blossom. The perfect aperitivo wine, served at 8–10°C.',1999.00,NULL,80,2022,'Veneto, Italy','Pinot Grigio',12.0,'assets/img/wine-10.svg',0),
(3,'Provence Rosé 2023','provence-rose-2023','Pale salmon-pink, dry and delicate — wild strawberry, citrus peel and a saline, mineral close. Summer in a glass.',3199.00,NULL,30,2023,'Côtes de Provence, France','Grenache / Cinsault',12.5,'assets/img/wine-11.svg',1),
(3,'Grenache Rosé 2023','grenache-rose-2023','Juicy Spanish rosado with watermelon, redcurrant and a hint of white pepper. Excellent with paella or grilled prawns.',1799.00,NULL,55,2023,'Navarra, Spain','Grenache',13.0,'assets/img/wine-12.svg',0),
(4,'Champagne Brut NV','champagne-brut-nv','Elegant non-vintage Champagne — brioche, green apple, fine persistent mousse. The house favourite for every celebration.',9999.00,8499.00,10,NULL,'Reims, France','Pinot Noir / Chardonnay',12.0,'assets/img/wine-13.svg',1),
(4,'Prosecco Superiore DOCG','prosecco-superiore-docg','Glera grapes from the Valdobbiadene hills — white peach, pear and citrus with a creamy, lively mousse.',2299.00,NULL,45,NULL,'Veneto, Italy','Glera',11.0,'assets/img/wine-14.svg',0),
(5,'Late Harvest Riesling 2019','late-harvest-riesling-2019','Botrytised riesling with apricot, honey and ginger. Lusciously sweet, lifted by bracing acidity. Serve with blue cheese or fruit tarts.',4299.00,NULL,18,2019,'Rheingau, Germany','Riesling',9.5,'assets/img/wine-15.svg',0),
(5,'Tawny Port 10 Year','tawny-port-10-year','A decade in oak gives caramel, walnut, dried fig and orange peel. Serve slightly chilled in a small glass after dinner.',5799.00,NULL,14,NULL,'Douro, Portugal','Touriga Nacional',20.0,'assets/img/wine-16.svg',0);

INSERT INTO users (name, email, password, phone, role) VALUES
('Admin User','admin@vinoecasa.com','$2y$10$aQiJr0lG1/gv3eTzOLHs.uOyx2T4s43UIIC0rmfYZpYVR.2osH8S2','+91 98765 43210','admin'),
('Priya Sharma','priya@example.com','$2y$10$qNM2u6soGfR.z2OQHqBDdee21js37p.mFbFKhBSGrYMa9qMVGYabi','+91 90000 11111','customer'),
('Rahul Mehta','rahul@example.com','$2y$10$qNM2u6soGfR.z2OQHqBDdee21js37p.mFbFKhBSGrYMa9qMVGYabi','+91 90000 22222','customer');

INSERT INTO reviews (product_id, user_id, author, rating, comment) VALUES
(1,NULL,'Sommelier Ananya',5,'Beautifully structured — cassis and cedar, tannins like velvet. Worth every rupee.'),
(3,NULL,'Karan V.',4,'Ripe and generous. Great with a ribeye. Slightly oaky but very polished.'),
(7,NULL,'Meera D.',5,'Tastes like the seaside. Crisp, chalky, perfect with oysters.'),
(8,NULL,'Aditya S.',5,'Best value Sauvignon Blanc I have bought online. Zippy and fresh.'),
(11,NULL,'Neha R.',5,'Pale, dry and so refreshing. Bought three more bottles after the first sip.'),
(13,NULL,'Vikram P.',5,'A classic Champagne — brioche and fine bubbles. Made our anniversary special.');

INSERT INTO orders (user_id, full_name, email, phone, address, city, postal_code, country, notes, payment_method, status, subtotal, shipping, total) VALUES
(2,'Priya Sharma','priya@example.com','+91 90000 11111','14 Palm Grove Apartments, Indiranagar','Bengaluru','560038','India','Please ring the doorbell twice.','upi','delivered',11798.00,0.00,11798.00),
(3,'Rahul Mehta','rahul@example.com','+91 90000 22222','B-22, Marine Drive','Mumbai','400020','India',NULL,'card','shipped',5498.00,0.00,5498.00),
(NULL,'Ananya Iyer','ananya.iyer@example.com','+91 98888 77777','Plot 9, Jubilee Hills','Hyderabad','500033','India','Gift wrap, please.','cod','processing',8499.00,0.00,8499.00),
(NULL,'Dev Patel','dev.patel@example.com','+91 97777 66666','3rd Cross, Koramangala','Bengaluru','560095','India',NULL,'cod','pending',3998.00,149.00,4147.00);

INSERT INTO order_items (order_id, product_id, product_name, price, qty) VALUES
(1,1,'Château Margaux Reserve 2018',8499.00,1),
(1,12,'Grenache Rosé 2023',1799.00,2),
(2,3,'Napa Valley Cabernet 2019',5499.00,1),
(3,1,'Château Margaux Reserve 2018',8499.00,1),
(4,10,'Pinot Grigio DOC 2022',1999.00,2);

INSERT INTO contact_messages (name, email, subject, message, is_read) VALUES
('Sanjay Rao','sanjay.rao@example.com','Private tasting enquiry','Do you host private tasting events for groups of 12? Looking for something next month.',0),
('Fatima Sheikh','fatima@example.com','Delivery to Goa','Can you deliver a case of rosé to Panjim, and what is the lead time?',0),
('Old Customer','kabir@example.com','Order #3 question','When is order #3 expected to ship to Hyderabad?',1);

INSERT INTO settings (setting_key, setting_value) VALUES
('shop_name','Vino e Casa'),
('shop_email','hello@vinoecasa.com'),
('shop_phone','+91 80 4567 8900'),
('shop_address','21 Cellar Street, Indiranagar, Bengaluru 560038'),
('currency_symbol','₹'),
('shipping_fee','149'),
('free_shipping_above','2499'),
('gst_percent','5'),
('tagline','Curated wines, delivered to your door');
