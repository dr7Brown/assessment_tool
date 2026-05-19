-- ============================================================
-- AceICT: 200 MCQ Questions — Year 1, S1/SS3 Connecting and Communicating Online
-- Ghana GES ICT Curriculum
-- Sub-strand: SS1 — Productivity Tools
-- Topics: Internet and WWW (IP addresses, DNS, URLs, browsers, search engines)
Email (Email structure, professional,writing, CC/BCC/attachments, email,safety)
Online Communication (Video conferencing, social,media, choosing the right, tool, responsible, use)
Netiquette and Digital Citizenship( 9 elements of digital,citizenship, rules of online, behaviour, digital divide)
Online Safety (Phishing, identity theft,cyberbullying, sakawa,malware, protective habits)
Digital Health (Eye strain, posture, sleep and screens, social media and,mental health)

-- Difficulty: 80 Easy, 80 Medium, 40 Hard
-- All options stored as separate rows in question_options
-- school_id=1, author_id=1, year_group=1
-- ============================================================



SET FOREIGN_KEY_CHECKS=0;

-- ────────────────────────────────────────────────────────────
-- EASY QUESTIONS (1–40)
-- ────────────────────────────────────────────────────────────

-- i want you to generate 200 mcq questions for year 1, Strand 1, sub-strand 3 (year1_s1_ss3). 
-- the questions should be based on the Ghana SHS curriculum for that sub-strands, 
-- and should be in the format of a question followed by four answer options (A, B, C, D) 
-- with one correct answer. please provide the questions in a SQL insert statement format 
-- to be added to the questions table. each question should have a unique id 
-- starting from 555.
question formate below: INSERT INTO questions (id,school_id,author_id,type,sub_strand,topic,bloom_level,difficulty,year_group,marks,question_text,explanation,is_active) VALUES
example: (555,1,1,'mcq','S1/SS3','Internet and WWW',1,'easy',1,1,'What does DNS stand for?','DNS stands for Domain Name System, which translates domain names into IP addresses.',1),

answer format below: INSERT INTO question_options (question_id,option_label,option_text,is_correct,sort_order) VALUES
example: (555,'A','Domain Name System',0,0),(555,'B','Digital Network Service',0,1),(555,'C','Data Naming Structure',0,2),(555,'D','Direct Network Search',1,3),

