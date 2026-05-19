-- ============================================================
-- AceICT — Complete Demo Seed Data
-- Run this AFTER schema.sql
-- Creates: 1 school, 3 users, 3 classes, 8 students,
--          40 questions, 3 tests, sample results
-- ============================================================

USE aceict;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE audit_log;
TRUNCATE TABLE spaced_repetition;
TRUNCATE TABLE badges;
TRUNCATE TABLE streaks;
TRUNCATE TABLE notifications;
TRUNCATE TABLE answers;
TRUNCATE TABLE attempts;
TRUNCATE TABLE test_assignments;
TRUNCATE TABLE test_questions;
TRUNCATE TABLE tests;
TRUNCATE TABLE class_students;
TRUNCATE TABLE classes;
TRUNCATE TABLE question_options;
TRUNCATE TABLE questions;
TRUNCATE TABLE sessions;
TRUNCATE TABLE users;
TRUNCATE TABLE schools;
SET FOREIGN_KEY_CHECKS = 1;

-- ── SCHOOL ────────────────────────────────────────────────────
INSERT INTO schools (id, name, ges_id, region, email, phone, plan, plan_expires) VALUES
(1, 'Bright Future Senior High School', 'GES-GA-2024-0142', 'Greater Accra',
 'admin@brightfuture.edu.gh', '0302-550-000', 'school', '2025-12-31');

-- ── STAFF ─────────────────────────────────────────────────────
-- Password for ALL demo accounts: demo1234
-- Hash: $2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uye29tyyW
INSERT INTO users (id, school_id, role, first_name, last_name, email, password_hash, avatar_color) VALUES
(1, 1, 'admin',   'Professor', 'Baah',   'admin@demo.aceict.app', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uye29tyyW', '#1A4F8A'),
(2, 1, 'teacher', 'Ama',       'Kyereh', 'ama@demo.aceict.app',   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uye29tyyW', '#C47D0E'),
(3, 1, 'teacher', 'Kweku',     'Boateng','kweku@demo.aceict.app', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uye29tyyW', '#5A3D9A');

-- ── STUDENTS ──────────────────────────────────────────────────
INSERT INTO users (id, school_id, role, first_name, last_name, email, password_hash, class_name, avatar_color) VALUES
(4,  1, 'student', 'Kofi',     'Mensah',   'kofi@demo.aceict.app',   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uye29tyyW', 'SHS2A', '#0F6E6A'),
(5,  1, 'student', 'Abena',    'Boateng',  'abena@demo.aceict.app',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uye29tyyW', 'SHS2A', '#1A7A4A'),
(6,  1, 'student', 'Emmanuel', 'Osei',     'emmaosei@demo.aceict.app','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uye29tyyW', 'SHS2A', '#B84022'),
(7,  1, 'student', 'Fatima',   'Alhassan', 'fatima@demo.aceict.app', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uye29tyyW', 'SHS2B', '#C47D0E'),
(8,  1, 'student', 'Akua',     'Asante',   'akua@demo.aceict.app',   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uye29tyyW', 'SHS2A', '#5A3D9A'),
(9,  1, 'student', 'Kwame',    'Owusu',    'kwame@demo.aceict.app',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uye29tyyW', 'SHS2A', '#1A4F8A'),
(10, 1, 'student', 'Ama',      'Darko',    'amadarko@demo.aceict.app','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uye29tyyW', 'SHS2B', '#3730A3'),
(11, 1, 'student', 'Nana',     'Adjei',    'nana@demo.aceict.app',   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uye29tyyW', 'SHS1A', '#0D5C35');

-- ── CLASSES ───────────────────────────────────────────────────
INSERT INTO classes (id, school_id, teacher_id, name, year_group) VALUES
(1, 1, 2, 'SHS2A', 2),
(2, 1, 2, 'SHS2B', 2),
(3, 1, 3, 'SHS1A', 1);

INSERT INTO class_students (class_id, student_id) VALUES
(1, 4),(1, 5),(1, 6),(1, 8),(1, 9),   -- SHS2A
(2, 7),(2, 10),                         -- SHS2B
(3, 11);                                -- SHS1A

-- ── STREAKS ───────────────────────────────────────────────────
INSERT INTO streaks (student_id, current_streak, longest_streak, last_activity, total_xp) VALUES
(4,  7,  12, CURDATE(), 2400),
(5,  12, 15, CURDATE(), 3600),
(6,  1,  5,  CURDATE(), 800),
(7,  3,  8,  CURDATE(), 1200),
(8,  9,  14, CURDATE(), 3100),
(9,  2,  6,  CURDATE(), 950),
(10, 15, 15, CURDATE(), 4500),
(11, 6,  9,  CURDATE(), 2100);

-- ════════════════════════════════════════════════════════════
-- QUESTION BANK  (40 questions, all sub-strands)
-- ════════════════════════════════════════════════════════════

-- ── S2/SS2 — Computer Security (10 questions) ─────────────────
INSERT INTO questions (id, school_id, author_id, type, sub_strand, topic, bloom_level, difficulty, year_group, marks, question_text, explanation) VALUES
(1, NULL, NULL, 'mcq', 'S2/SS2', 'CIA Triad', 'Remember', 'Easy', 1, 1,
 'What does CIA stand for in the context of information security?',
 'CIA = Confidentiality (only authorised access), Integrity (data accurate and unaltered), Availability (accessible when needed). These are the three pillars of information security.'),
(2, NULL, NULL, 'mcq', 'S2/SS2', 'CIA Triad', 'Understand', 'Medium', 1, 1,
 'A hacker changes a student\'s grade in the school database without authorisation. Which pillar of the CIA Triad has been violated?',
 'Integrity means data is accurate and has not been tampered with. Changing a grade without authorisation directly violates the Integrity pillar.'),
(3, NULL, NULL, 'mcq', 'S2/SS2', 'Malware', 'Understand', 'Medium', 1, 1,
 'Which type of malware self-replicates and spreads across networks automatically, without any user action required?',
 'A worm self-replicates independently across networks. A virus needs a user to run an infected file. A trojan disguises itself as legitimate software.'),
(4, NULL, NULL, 'mcq', 'S2/SS2', 'Attacks', 'Apply', 'Easy', 1, 1,
 'A fake MTN MoMo SMS tells a student their account will be suspended and asks them to click a link and enter their PIN. This is an example of:',
 'Phishing uses fraudulent messages impersonating trusted entities (here MTN) to steal credentials. The urgency and suspicious link are classic phishing signs. Report to 0800-292-292 (CSA Ghana).'),
(5, NULL, NULL, 'mcq', 'S2/SS2', 'Countermeasures', 'Remember', 'Easy', 1, 1,
 'The 3-2-1 backup rule states you should keep 3 copies of data, on 2 different media types, with at least 1 copy stored:',
 'The "1" in 3-2-1 means one copy must be offsite (cloud, another building). A fire destroying the office would also destroy any backup stored in the same room.'),
(6, NULL, NULL, 'mcq', 'S2/SS2', 'Ghana Laws', 'Remember', 'Medium', 1, 1,
 'Which Ghana law establishes the Cyber Security Authority and criminalises hacking with up to 10 years imprisonment?',
 'The Cybersecurity Act 2020 (Act 1038) created the Cyber Security Authority Ghana (cyber.gov.gh, toll-free: 0800-292-292). It criminalises hacking, cyberbullying and spreading false information online.'),
(7, NULL, NULL, 'mcq', 'S2/SS2', 'Encryption', 'Analyse', 'Hard', 1, 1,
 'Symmetric encryption uses one shared key. Asymmetric encryption uses a key pair. Which statement CORRECTLY describes asymmetric encryption?',
 'Asymmetric (RSA/ECC) uses a PUBLIC key to encrypt and a PRIVATE key to decrypt. Anyone can encrypt using your public key but only you can decrypt with your private key. Used in HTTPS and digital signatures.'),
(8, NULL, NULL, 'mcq', 'S2/SS2', 'CIA Triad', 'Apply', 'Medium', 1, 1,
 'A DDoS attack floods a server with millions of requests from thousands of computers, making it unreachable to real users. Which CIA pillar is violated?',
 'Availability means systems are accessible when needed. A DDoS attack makes the system unavailable to legitimate users, directly attacking Availability.'),
(9, NULL, NULL, 'mcq', 'S2/SS2', 'Ghana Laws', 'Analyse', 'Hard', 1, 1,
 'A hospital collects patient home addresses for sending appointment letters, then sells that data to an insurance company. Which principle of Ghana\'s Data Protection Act 2012 (Act 843) is violated?',
 'Act 843\'s "purpose limitation" principle means data collected for one stated reason cannot be used for another without fresh consent. The hospital collected addresses for appointments but used them commercially — a violation.'),
(10, NULL, NULL, 'mcq', 'S2/SS2', 'Wireless Security', 'Remember', 'Easy', 1, 1,
 'Which wireless security protocol is completely broken and should NEVER be used on any network?',
 'WEP (Wired Equivalent Privacy) has been broken since 2001 and can be cracked in under 2 minutes with free tools. Always use WPA2 (minimum) or WPA3. Many Ghana home routers still default to WEP — always change it.');

INSERT INTO question_options (question_id, option_label, option_text, is_correct, sort_order) VALUES
(1,'A','Central Intelligence Agency',0,1),(1,'B','Confidentiality, Integrity, Availability',1,2),(1,'C','Computer Integrity Assessment',0,3),(1,'D','Cyber Intelligence Architecture',0,4),
(2,'A','Confidentiality',0,1),(2,'B','Integrity',1,2),(2,'C','Availability',0,3),(2,'D','Authentication',0,4),
(3,'A','Virus',0,1),(3,'B','Worm',1,2),(3,'C','Trojan horse',0,3),(3,'D','Adware',0,4),
(4,'A','Hacking',0,1),(4,'B','Ransomware',0,2),(4,'C','Phishing',1,3),(4,'D','A DoS attack',0,4),
(5,'A','On the same computer',0,1),(5,'B','Encrypted only',0,2),(5,'C','Offsite',1,3),(5,'D','On a USB drive',0,4),
(6,'A','Electronic Transactions Act 2008 (Act 772)',0,1),(6,'B','Data Protection Act 2012 (Act 843)',0,2),(6,'C','Cybersecurity Act 2020 (Act 1038)',1,3),(6,'D','Electronic Communications Act 2008',0,4),
(7,'A','Two identical keys are used for both operations',0,1),(7,'B','A public key encrypts and a private key decrypts',1,2),(7,'C','No keys are used — only hashing',0,3),(7,'D','A password replaces the key entirely',0,4),
(8,'A','Confidentiality',0,1),(8,'B','Integrity',0,2),(8,'C','Availability',1,3),(8,'D','Non-repudiation',0,4),
(9,'A','Accuracy principle',0,1),(9,'B','Purpose limitation principle',1,2),(9,'C','Data minimisation principle',0,3),(9,'D','Storage limitation principle',0,4),
(10,'A','WPA (Wi-Fi Protected Access)',0,1),(10,'B','WPA2',0,2),(10,'C','WEP (Wired Equivalent Privacy)',1,3),(10,'D','WPA3',0,4);

-- ── S2/SS1 — Network Systems (8 questions) ────────────────────
INSERT INTO questions (id, school_id, author_id, type, sub_strand, topic, bloom_level, difficulty, year_group, marks, question_text, explanation) VALUES
(11, NULL, NULL, 'mcq', 'S2/SS1', 'Network Types', 'Remember', 'Easy', 1, 1,
 'Which network type connects personal devices (phone, earphones, smartwatch) over a very short range, typically using Bluetooth?',
 'PAN = Personal Area Network. Covers a range of about 10 metres. Uses Bluetooth or USB. Examples: connecting AirPods to your phone, syncing a smartwatch.'),
(12, NULL, NULL, 'mcq', 'S2/SS1', 'Topologies', 'Remember', 'Easy', 1, 1,
 'Which network topology connects ALL devices to a single central switch, and is the most common in school computer labs?',
 'Star topology: every device has its own dedicated cable to a central switch. Failure of one device does not affect others. However, if the central switch fails, ALL devices lose connectivity.'),
(13, NULL, NULL, 'mcq', 'S2/SS1', 'Network Devices', 'Understand', 'Medium', 1, 1,
 'A router connects different networks using IP addresses. What does a switch do?',
 'A switch connects devices WITHIN the same network (LAN) using MAC addresses. It sends data only to the specific port where the destination device is connected — unlike a hub which broadcasts to all ports.'),
(14, NULL, NULL, 'mcq', 'S2/SS1', 'OSI Model', 'Remember', 'Easy', 1, 1,
 'The OSI model has how many layers, and what is the correct order from Layer 7 down to Layer 1?',
 'OSI has 7 layers. Layer 7→1: Application, Presentation, Session, Transport, Network, Data Link, Physical. Mnemonic (7→1): "All People Seem To Need Data Processing".'),
(15, NULL, NULL, 'mcq', 'S2/SS1', 'OSI Model', 'Analyse', 'Hard', 1, 1,
 'At which OSI layer does a switch operate, and what addressing does it use?',
 'Switches operate at Layer 2 (Data Link) using MAC addresses. Routers operate at Layer 3 (Network) using IP addresses. Hubs operate at Layer 1 (Physical) with no intelligence.'),
(16, NULL, NULL, 'mcq', 'S2/SS1', 'Network Media', 'Understand', 'Medium', 1, 1,
 'Which transmission medium is completely immune to electromagnetic interference (EMI) and is used in Ghana\'s national fibre backbone?',
 'Fibre optic cables transmit LIGHT through glass fibres — completely immune to EMI. Ghana\'s 5,000km national fibre backbone, and the SAT-3 and GLO-1 undersea cables, all use fibre optic.'),
(17, NULL, NULL, 'mcq', 'S2/SS1', 'Protocols', 'Understand', 'Medium', 1, 1,
 'DNS (Domain Name System) translates a domain name like ghanaweb.com into a numerical IP address. What is this process called?',
 'DNS resolution: your computer queries a DNS server which returns the IP address for the domain. Without DNS, you would need to memorise IP addresses like 197.251.60.10 instead of typing ghanaweb.com.'),
(18, NULL, NULL, 'mcq', 'S2/SS1', 'IP Addressing', 'Apply', 'Hard', 1, 1,
 'A school network uses 192.168.10.0/24. How many USABLE host addresses does this provide?',
 '2^8 = 256 total. Subtract 2: the network address (192.168.10.0) and broadcast address (192.168.10.255) cannot be assigned to hosts. Result: 254 usable host addresses.');

INSERT INTO question_options (question_id, option_label, option_text, is_correct, sort_order) VALUES
(11,'A','LAN — Local Area Network',0,1),(11,'B','MAN — Metropolitan Area Network',0,2),(11,'C','PAN — Personal Area Network',1,3),(11,'D','WAN — Wide Area Network',0,4),
(12,'A','Bus',0,1),(12,'B','Ring',0,2),(12,'C','Star',1,3),(12,'D','Mesh',0,4),
(13,'A','Connects different networks using IP addresses',0,1),(13,'B','Connects devices within the same network using MAC addresses',1,2),(13,'C','Converts digital signals to analogue for phone lines',0,3),(13,'D','Broadcasts data to all devices on the internet',0,4),
(14,'A','5 layers — Application down to Physical',0,1),(14,'B','7 layers — Application, Presentation, Session, Transport, Network, Data Link, Physical',1,2),(14,'C','4 layers — Application, Transport, Internet, Network Access',0,3),(14,'D','7 layers — Physical up to Application',0,4),
(15,'A','Layer 1 — Physical, using voltage signals',0,1),(15,'B','Layer 2 — Data Link, using MAC addresses',1,2),(15,'C','Layer 3 — Network, using IP addresses',0,3),(15,'D','Layer 4 — Transport, using port numbers',0,4),
(16,'A','Twisted pair (UTP) — cheapest and most flexible',0,1),(16,'B','Coaxial cable — used in older cable TV networks',0,2),(16,'C','Fibre optic — transmits light, immune to EMI',1,3),(16,'D','Wi-Fi — uses radio waves, fastest for long range',0,4),
(17,'A','IP allocation','b',1),(17,'B','DNS resolution — translating domain names to IP addresses',1,2),(17,'C','Packet routing — choosing the fastest path',0,3),(17,'D','SSL handshake — establishing encrypted connection',0,4),
(18,'A','256 addresses',0,1),(18,'B','254 addresses',1,2),(18,'C','255 addresses',0,3),(18,'D','128 addresses',0,4);

-- ── S1/SS2 — Emerging Technologies (8 questions) ──────────────
INSERT INTO questions (id, school_id, author_id, type, sub_strand, topic, bloom_level, difficulty, year_group, marks, question_text, explanation) VALUES
(19, NULL, NULL, 'mcq', 'S1/SS2', 'AI Basics', 'Remember', 'Easy', 1, 1,
 'Only one type of AI exists today. Which is it?',
 'Only Narrow AI exists — designed for ONE specific task (face recognition, spam filtering, chess). General AI (human-level intelligence across all domains) does not yet exist. All current AI tools like ChatGPT are Narrow AI.'),
(20, NULL, NULL, 'mcq', 'S1/SS2', 'AI Applications', 'Apply', 'Easy', 1, 1,
 'Ghana\'s Zipline service uses AI-powered drones to deliver which items to hospitals in the Volta Region?',
 'Zipline uses fixed-wing AI drones to deliver blood products, medicines and vaccines to hospitals across the Volta Region, reducing delivery time from hours to 30 minutes. A key example of AI in Ghana\'s healthcare sector.'),
(21, NULL, NULL, 'mcq', 'S1/SS2', 'Cloud Computing', 'Understand', 'Easy', 1, 1,
 'A business uses Google Workspace (Gmail, Docs, Sheets) for all its work. Which cloud service model is this?',
 'SaaS = Software as a Service. The software is hosted, maintained and updated by Google — the business just uses it via a browser. IaaS = renting servers. PaaS = renting a platform to build your own apps.'),
(22, NULL, NULL, 'mcq', 'S1/SS2', 'Cloud Computing', 'Analyse', 'Hard', 1, 1,
 'A Ghanaian startup wants to build a new mobile banking app without buying or managing servers. Which cloud model should they use?',
 'PaaS (Platform as a Service) provides the platform — servers, OS, database, runtime — so developers focus only on writing the app. AWS Elastic Beanstalk, Google App Engine and Heroku are PaaS examples.'),
(23, NULL, NULL, 'mcq', 'S1/SS2', 'Big Data', 'Remember', 'Medium', 1, 1,
 'Which of the 5 Vs of Big Data describes the SPEED at which data is generated and must be processed?',
 'Velocity = speed. GhIPSS must process thousands of MoMo transactions per second and detect fraud in real-time (milliseconds). Volume = size. Variety = different formats. Veracity = quality. Value = useful insights.'),
(24, NULL, NULL, 'mcq', 'S1/SS2', 'Fintech', 'Remember', 'Medium', 1, 1,
 'Mobile Money (MoMo) operations in Ghana are regulated by which institution?',
 'The Bank of Ghana regulates all mobile money operators under the Payment Systems and Services Act 2019 (Act 987). It ensures trust accounts are 100% funded and sets security standards. MTN, Telecel and AirtelTigo must comply.'),
(25, NULL, NULL, 'mcq', 'S1/SS2', 'IoT', 'Understand', 'Medium', 1, 1,
 'The Internet of Things (IoT) connects physical devices to the internet. Which of the following is the BEST example of IoT being used in Ghanaian agriculture?',
 'Smart soil moisture sensors connected to the internet allow farmers to remotely monitor and automatically trigger irrigation — a direct IoT application. CSIR-SARI and AgroCenta use IoT-based tools for Ghana\'s cocoa and vegetable farmers.'),
(26, NULL, NULL, 'mcq', 'S1/SS2', 'AI Applications', 'Analyse', 'Hard', 1, 1,
 'KNUST researchers developed an AI system that analyses microscope images to detect malaria parasites with 94% accuracy. What type of AI learning is this?',
 'Supervised learning: the model was trained on thousands of labelled microscope images (malaria positive / negative) and learned to classify new images. This is the most common ML technique for image classification tasks.');

INSERT INTO question_options (question_id, option_label, option_text, is_correct, sort_order) VALUES
(19,'A','General AI — can perform any intellectual task a human can',0,1),(19,'B','Narrow AI — designed for one specific task',1,2),(19,'C','Super AI — exceeds human intelligence in all areas',0,3),(19,'D','Both General and Narrow AI exist today',0,4),
(20,'A','Food and groceries',0,1),(20,'B','School examination papers',0,2),(20,'C','Blood products, medicines and vaccines',1,3),(20,'D','Ghana Card documents',0,4),
(21,'A','IaaS — Infrastructure as a Service',0,1),(21,'B','PaaS — Platform as a Service',0,2),(21,'C','SaaS — Software as a Service',1,3),(21,'D','DaaS — Data as a Service',0,4),
(22,'A','IaaS — they rent raw servers and manage everything themselves',0,1),(22,'B','SaaS — they use someone else\'s finished software',0,2),(22,'C','PaaS — they get the platform and focus only on building the app',1,3),(22,'D','On-premises — they buy their own servers',0,4),
(23,'A','Volume',0,1),(23,'B','Velocity',1,2),(23,'C','Variety',0,3),(23,'D','Veracity',0,4),
(24,'A','National Communications Authority (NCA)',0,1),(24,'B','Ghana Revenue Authority (GRA)',0,2),(24,'C','Bank of Ghana',1,3),(24,'D','National Insurance Commission (NIC)',0,4),
(25,'A','A farmer using a mobile phone to call the market',0,1),(25,'B','Smart soil moisture sensors that automatically trigger irrigation remotely',1,2),(25,'C','A tractor with a GPS screen',0,3),(25,'D','Using Excel to track farm expenses',0,4),
(26,'A','Unsupervised learning — found patterns without labels',0,1),(26,'B','Reinforcement learning — learned by trial and error',0,2),(26,'C','Supervised learning — trained on labelled images',1,3),(26,'D','Transfer learning — used a pre-trained model unchanged',0,4);

-- ── S1/SS3 — Online Safety & E-Commerce (7 questions) ─────────
INSERT INTO questions (id, school_id, author_id, type, sub_strand, topic, bloom_level, difficulty, year_group, marks, question_text, explanation) VALUES
(27, NULL, NULL, 'mcq', 'S1/SS3', 'Internet vs WWW', 'Understand', 'Medium', 1, 1,
 'The Internet and the World Wide Web (WWW) are often confused. Which statement CORRECTLY distinguishes them?',
 'The Internet = the global physical infrastructure (cables, routers, satellites). The WWW = websites and webpages that travel on the internet using HTTP/HTTPS. Email, video calls and online gaming also use the internet but are NOT the WWW.'),
(28, NULL, NULL, 'mcq', 'S1/SS3', 'Online Safety', 'Apply', 'Easy', 1, 1,
 'Before entering your MTN MoMo PIN or personal details on a website, which of the following should you check FIRST?',
 'HTTPS means the connection is encrypted using TLS. The padlock icon in the browser address bar confirms this. Without HTTPS, your data is transmitted in plain text and can be intercepted. Never enter sensitive data on HTTP-only sites.'),
(29, NULL, NULL, 'mcq', 'S1/SS3', 'E-Commerce', 'Apply', 'Easy', 1, 1,
 'Customers selling used phones and electronics to other individuals on Tonaton Ghana is an example of which e-commerce type?',
 'C2C = Consumer to Consumer. Individuals buy and sell directly to each other via a platform. Tonaton, Jiji Ghana, and Facebook Marketplace are C2C platforms. B2C = business sells to customer. B2B = business to business.'),
(30, NULL, NULL, 'mcq', 'S1/SS3', 'E-Commerce', 'Understand', 'Medium', 1, 1,
 'GhIPSS manages Ghana\'s GhQR code system. What is the main benefit of GhQR for merchants?',
 'GhQR is Ghana\'s national QR payment standard. A merchant displays ONE QR code. Any customer from ANY bank or MoMo network can scan it to pay — without the merchant needing separate terminals for MTN, Telecel, AirtelTigo and different banks.'),
(31, NULL, NULL, 'mcq', 'S1/SS3', 'HTML', 'Remember', 'Easy', 1, 1,
 'Which HTML tag is used to create a clickable hyperlink to another webpage?',
 'The <a> (anchor) tag creates hyperlinks. The href attribute specifies the destination URL. Example: <a href="https://ghana.gov.gh">Visit Ghana.gov.gh</a>. The closing </a> tag is required.'),
(32, NULL, NULL, 'mcq', 'S1/SS3', 'CSS', 'Remember', 'Medium', 1, 1,
 'What is the correct order of the CSS box model layers from inside to outside?',
 'Box model (inside → outside): Content → Padding → Border → Margin. Total element width = content + left padding + right padding + left border + right border + left margin + right margin.'),
(33, NULL, NULL, 'mcq', 'S1/SS3', 'Online Safety', 'Analyse', 'Hard', 1, 1,
 'You receive a WhatsApp voice note from an unknown number claiming to be an NHIA officer, saying your health insurance will be cancelled unless you send GHS 50 to a MoMo number. What should you do?',
 'This is a social engineering / vishing (voice phishing) attack. NHIA never asks for payment via MoMo to unknown numbers. Verify ONLY at nhia.gov.gh or call 0800-835-000. Report the incident to CSA Ghana at 0800-292-292.');

INSERT INTO question_options (question_id, option_label, option_text, is_correct, sort_order) VALUES
(27,'A','They are exactly the same thing — just different names',0,1),(27,'B','The Internet is the physical network infrastructure; the WWW is websites that use it',1,2),(27,'C','The WWW was created before the Internet',0,3),(27,'D','The Internet is only for email; the WWW handles everything else',0,4),
(28,'A','The website has a colourful logo',0,1),(28,'B','The URL starts with HTTPS and shows a padlock icon',1,2),(28,'C','The website loads quickly',0,3),(28,'D','The website asks for your date of birth',0,4),
(29,'A','B2B — Business to Business',0,1),(29,'B','B2C — Business to Consumer',0,2),(29,'C','C2C — Consumer to Consumer',1,3),(29,'D','G2C — Government to Consumer',0,4),
(30,'A','Merchants can avoid paying transaction fees entirely',0,1),(30,'B','One QR code accepts payment from any bank or MoMo network',1,2),(30,'C','Customers can pay in foreign currencies automatically',0,3),(30,'D','Merchants receive payments instantly in cash',0,4),
(31,'A','<link href="...">',0,1),(31,'B','<url href="...">',0,2),(31,'C','<a href="...">',1,3),(31,'D','<nav to="...">',0,4),
(32,'A','Margin → Border → Padding → Content',0,1),(32,'B','Content → Padding → Border → Margin',1,2),(32,'C','Padding → Content → Margin → Border',0,3),(32,'D','Border → Margin → Content → Padding',0,4),
(33,'A','Send the GHS 50 immediately to avoid losing your cover',0,1),(33,'B','Ask the caller for more details about your policy',0,2),(33,'C','Block the number and verify by calling NHIA directly at 0800-835-000',1,3),(33,'D','Forward the voice note to 10 contacts to warn them',0,4);

-- ── S1/SS1 — Productivity Tools (7 questions) ─────────────────
INSERT INTO questions (id, school_id, author_id, type, sub_strand, topic, bloom_level, difficulty, year_group, marks, question_text, explanation) VALUES
(34, NULL, NULL, 'mcq', 'S1/SS1', 'Spreadsheets', 'Remember', 'Easy', 1, 1,
 'Which Excel function returns the HIGHEST value from a range of cells?',
 '=MAX(range) returns the largest value. =MIN() = smallest. =AVERAGE() = mean. =SUM() = total. =COUNT() = number of cells containing numbers. These are the five most commonly examined Excel functions in WASSCE ICT.'),
(35, NULL, NULL, 'mcq', 'S1/SS1', 'Spreadsheets', 'Understand', 'Medium', 1, 1,
 'The VLOOKUP function in Excel searches for a value. Where does it search?',
 'VLOOKUP (Vertical Lookup) searches ONLY the FIRST (leftmost) column of the table_array for the lookup value. The lookup item must ALWAYS be in column 1. Common exam error: putting the lookup value in the wrong column.'),
(36, NULL, NULL, 'mcq', 'S1/SS1', 'Word Processing', 'Understand', 'Medium', 1, 1,
 'In Microsoft Word, which feature allows you to automatically generate a Table of Contents linked to the document headings?',
 'When you apply built-in Heading styles (Heading 1, 2, 3) to section titles, Word can auto-generate a Table of Contents (References → Table of Contents) that links directly to those headings and updates when content changes.'),
(37, NULL, NULL, 'mcq', 'S1/SS1', 'Databases', 'Understand', 'Medium', 1, 1,
 'In a database table, a PRIMARY KEY:',
 'A primary key uniquely identifies every record. No two records can have the same primary key value, and it cannot be NULL (empty). Example: StudentID uniquely identifies each student — no two students share the same ID.'),
(38, NULL, NULL, 'mcq', 'S1/SS1', 'Spreadsheets', 'Apply', 'Hard', 1, 1,
 'A teacher has student names in column A and scores in column B. She wants to display "Pass" if a score is 50 or above, and "Fail" otherwise. Which formula is correct for cell C2?',
 '=IF(B2>=50,"Pass","Fail") is the correct syntax. IF(logical_test, value_if_true, value_if_false). The condition B2>=50 evaluates to TRUE or FALSE. Text values in Excel formulas must always be in double quotation marks.'),
(39, NULL, NULL, 'mcq', 'S1/SS1', 'Databases', 'Analyse', 'Hard', 1, 1,
 'What is the difference between a query and a report in Microsoft Access?',
 'A query retrieves and filters data from tables — it is the question you ask the database. A report is a formatted, printable presentation of that data — designed for professional output. Queries feed data to reports.'),
(40, NULL, NULL, 'mcq', 'S1/SS1', 'Word Processing', 'Apply', 'Medium', 1, 1,
 'A student uses Track Changes in Microsoft Word to edit a classmate\'s essay. What does this feature do?',
 'Track Changes records every edit (insertions in underline, deletions in strikethrough) with the editor\'s name and timestamp. The original author can Accept or Reject each change individually. Essential for collaborative document editing.');

INSERT INTO question_options (question_id, option_label, option_text, is_correct, sort_order) VALUES
(34,'A','=SUM()',0,1),(34,'B','=AVERAGE()',0,2),(34,'C','=MAX()',1,3),(34,'D','=COUNT()',0,4),
(35,'A','Any column specified by the user',0,1),(35,'B','Only the first (leftmost) column of the table_array',1,2),(35,'C','Only the last column of the table',0,3),(35,'D','All columns simultaneously',0,4),
(36,'A','Mail Merge',0,1),(36,'B','Track Changes',0,2),(36,'C','Heading Styles and Table of Contents',1,3),(36,'D','Find and Replace',0,4),
(37,'A','Must be the first field in the table',0,1),(37,'B','Uniquely identifies each record and cannot be NULL or duplicated',1,2),(37,'C','Contains the largest amount of data',0,3),(37,'D','Determines the default sort order',0,4),
(38,'A','=IF(B2>50,Pass,Fail)',0,1),(38,'B','=IF(B2>=50,"Pass","Fail")',1,2),(38,'C','=IF(B2>=50,PASS,FAIL)',0,3),(38,'D','=IFF(B2>=50,"Pass","Fail")',0,4),
(39,'A','They are identical — both retrieve data from tables',0,1),(39,'B','A query retrieves/filters data; a report formats it for printing',1,2),(39,'C','A report retrieves data; a query formats it',0,3),(39,'D','Queries are for numbers only; reports handle text',0,4),
(40,'A','Automatically corrects spelling mistakes',0,1),(40,'B','Saves a backup copy of the document',0,2),(40,'C','Records all edits with author name, allowing accept or reject',1,3),(40,'D','Protects the document with a password',0,4);

-- ════════════════════════════════════════════════════════════
-- TESTS  (3 published tests)
-- ════════════════════════════════════════════════════════════
INSERT INTO tests (id, school_id, creator_id, title, type, status, time_limit_min, max_attempts, randomise_qs, randomise_opts, show_feedback, available_from, due_at) VALUES
(1, 1, 2, 'S2/SS2 — Computer Security Quiz',     'quiz', 'published', 45, 2, 1, 1, 1, NOW() - INTERVAL 2 DAY, NOW() + INTERVAL 5 DAY),
(2, 1, 2, 'S2/SS1 — Network Systems Quiz',       'quiz', 'published', 30, 1, 1, 1, 1, NOW() - INTERVAL 1 DAY, NOW() + INTERVAL 7 DAY),
(3, 1, 2, 'Term 2 WASSCE Mock — Full Paper',     'mock', 'published', 150,1, 1, 1, 0, NOW(),               NOW() + INTERVAL 3 DAY);

-- Link questions to tests
INSERT INTO test_questions (test_id, question_id, sort_order, section) VALUES
-- Test 1: Computer Security (Q1-10)
(1,1,1,'A'),(1,2,2,'A'),(1,3,3,'A'),(1,4,4,'A'),(1,5,5,'A'),
(1,6,6,'A'),(1,7,7,'A'),(1,8,8,'A'),(1,9,9,'A'),(1,10,10,'A'),
-- Test 2: Network Systems (Q11-18)
(2,11,1,'A'),(2,12,2,'A'),(2,13,3,'A'),(2,14,4,'A'),(2,15,5,'A'),(2,16,6,'A'),(2,17,7,'A'),(2,18,8,'A'),
-- Test 3: Full mock (all 40 questions)
(3,1,1,'A'),(3,2,2,'A'),(3,3,3,'A'),(3,4,4,'A'),(3,5,5,'A'),
(3,6,6,'A'),(3,7,7,'A'),(3,8,8,'A'),(3,9,9,'A'),(3,10,10,'A'),
(3,11,11,'A'),(3,12,12,'A'),(3,13,13,'A'),(3,14,14,'A'),(3,15,15,'A'),
(3,16,16,'A'),(3,17,17,'A'),(3,18,18,'A'),
(3,19,19,'B'),(3,20,20,'B'),(3,21,21,'B'),(3,22,22,'B'),(3,23,23,'B'),
(3,24,24,'B'),(3,25,25,'B'),(3,26,26,'B'),
(3,27,27,'C'),(3,28,28,'C'),(3,29,29,'C'),(3,30,30,'C'),(3,31,31,'C'),(3,32,32,'C'),(3,33,33,'C'),
(3,34,34,'C'),(3,35,35,'C'),(3,36,36,'C'),(3,37,37,'C'),(3,38,38,'C'),(3,39,39,'C'),(3,40,40,'C');

-- Assign tests to classes
INSERT INTO test_assignments (test_id, class_id) VALUES
(1,1),(1,2),   -- Security quiz: SHS2A + SHS2B
(2,1),(2,2),   -- Networks quiz: SHS2A + SHS2B
(3,1),(3,2);   -- Mock: SHS2A + SHS2B

-- ════════════════════════════════════════════════════════════
-- SAMPLE ATTEMPT + ANSWERS for Kofi Mensah (student id=4)
-- ════════════════════════════════════════════════════════════
INSERT INTO attempts (id, test_id, student_id, attempt_num, status, started_at, submitted_at, time_taken_s, score_auto, max_score, ip_address) VALUES
(1, 1, 4, 1, 'marked', NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY + INTERVAL 40 MINUTE, 2400, 7, 10, '127.0.0.1');

-- Kofi's answers to the security quiz (7 correct out of 10)
INSERT INTO answers (attempt_id, question_id, selected_opts, is_correct, marks_awarded, time_on_q_s) VALUES
(1, 1,  '["B"]', 1, 1, 45),   -- CIA: correct
(1, 2,  '["B"]', 1, 1, 60),   -- Grade change: correct
(1, 3,  '["A"]', 0, 0, 55),   -- Malware: wrong (chose Virus instead of Worm)
(1, 4,  '["C"]', 1, 1, 40),   -- Phishing: correct
(1, 5,  '["C"]', 1, 1, 35),   -- Backup: correct
(1, 6,  '["A"]', 0, 0, 70),   -- Ghana law: wrong (chose Act 772)
(1, 7,  '["B"]', 1, 1, 90),   -- Encryption: correct
(1, 8,  '["C"]', 1, 1, 50),   -- DDoS: correct
(1, 9,  '["A"]', 0, 0, 80),   -- Data Protection: wrong (chose accuracy)
(1, 10, '["C"]', 1, 1, 45);   -- WEP: correct

-- Second attempt by Abena (higher score)
INSERT INTO attempts (id, test_id, student_id, attempt_num, status, started_at, submitted_at, time_taken_s, score_auto, max_score, ip_address) VALUES
(2, 1, 5, 1, 'marked', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY + INTERVAL 35 MINUTE, 2100, 9, 10, '127.0.0.1');

INSERT INTO answers (attempt_id, question_id, selected_opts, is_correct, marks_awarded, time_on_q_s) VALUES
(2, 1, '["B"]', 1, 1, 30),(2, 2, '["B"]', 1, 1, 45),(2, 3, '["B"]', 1, 1, 50),
(2, 4, '["C"]', 1, 1, 35),(2, 5, '["C"]', 1, 1, 30),(2, 6, '["C"]', 1, 1, 60),
(2, 7, '["B"]', 1, 1, 75),(2, 8, '["C"]', 1, 1, 40),(2, 9, '["A"]', 0, 0, 65),
(2,10, '["B"]', 0, 0, 40);

-- Networks quiz attempt by Kofi
INSERT INTO attempts (id, test_id, student_id, attempt_num, status, started_at, submitted_at, time_taken_s, score_auto, max_score) VALUES
(3, 2, 4, 1, 'marked', NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 5 DAY + INTERVAL 25 MINUTE, 1500, 5, 8);

INSERT INTO answers (attempt_id, question_id, selected_opts, is_correct, marks_awarded, time_on_q_s) VALUES
(3,11,'["C"]',1,1,40),(3,12,'["C"]',1,1,35),(3,13,'["B"]',1,1,55),
(3,14,'["B"]',1,1,60),(3,15,'["A"]',0,0,80),(3,16,'["C"]',1,1,45),
(3,17,'["A"]',0,0,70),(3,18,'["B"]',1,1,90);

-- ── Spaced repetition for Kofi's wrong answers ────────────────
INSERT INTO spaced_repetition (student_id, question_id, ease_factor, interval_d, repetitions, next_review, last_review) VALUES
(4, 3,  2.50, 1, 0, CURDATE() + INTERVAL 1 DAY, CURDATE()),
(4, 6,  2.50, 1, 0, CURDATE() + INTERVAL 1 DAY, CURDATE()),
(4, 9,  2.50, 1, 0, CURDATE() + INTERVAL 1 DAY, CURDATE()),
(4, 15, 2.50, 1, 0, CURDATE() + INTERVAL 1 DAY, CURDATE()),
(4, 17, 2.50, 1, 0, CURDATE() + INTERVAL 1 DAY, CURDATE());

-- ── Badges for top performers ─────────────────────────────────
INSERT INTO badges (student_id, badge_key) VALUES
(5,  'first_quiz'),
(5,  'perfect_score'),
(10, 'first_quiz'),
(10, 'perfect_score'),
(10, '7_day_streak'),
(10, '15_day_streak'),
(4,  'first_quiz'),
(4,  '7_day_streak');

-- ── Notifications ─────────────────────────────────────────────
INSERT INTO notifications (user_id, type, title, body, is_read) VALUES
(4, 'result_ready',   'Your Security Quiz result is ready',         'You scored 70% (7/10). Review your wrong answers to improve.', 0),
(4, 'test_due',       'Network Systems Quiz due in 2 days',         'Complete the S2/SS1 quiz before Thursday to avoid missing marks.', 0),
(4, 'test_due',       'WASSCE Mock due Friday',                     'The full Term 2 mock paper closes Friday at 5pm. 150 minutes. 40 questions.', 1),
(2, 'essay_pending',  '5 essays awaiting your review',              'Emmanuel Osei and 4 others have submitted essay answers. Open Marking Queue.', 0);

-- ── Verify seed completed ─────────────────────────────────────
SELECT
  (SELECT COUNT(*) FROM schools)          AS schools,
  (SELECT COUNT(*) FROM users)            AS users,
  (SELECT COUNT(*) FROM classes)          AS classes,
  (SELECT COUNT(*) FROM class_students)   AS class_members,
  (SELECT COUNT(*) FROM questions)        AS questions,
  (SELECT COUNT(*) FROM question_options) AS options,
  (SELECT COUNT(*) FROM tests)            AS tests,
  (SELECT COUNT(*) FROM test_questions)   AS test_questions,
  (SELECT COUNT(*) FROM attempts)         AS attempts,
  (SELECT COUNT(*) FROM answers)          AS answers,
  (SELECT COUNT(*) FROM streaks)          AS streaks,
  (SELECT COUNT(*) FROM badges)           AS badges,
  (SELECT COUNT(*) FROM notifications)    AS notifications;
