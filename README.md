=== pdf2p2 ===
Contributors: ManikinSaute
Tags: pdf, import, ocr
Requires at least: 6.7
Tested up to: 6.8
Requires PHP: 8.2
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html



# pdf2p2

There was already a “PDF2Post” plugin, so we shortened and evolved it into **pdf2p2**.  

---

## Overview  

### This plugin is not yet production ready. This is a proof of concept   

**pdf2p2** is a WordPress plugin for importing, processing, and publishing PDFs as posts. It automates the entire flow:   

- Parse an **RSS feed URL** to extract PDF links  
- Download and save PDFs into the **Media Library**  
- Create posts with:  
  - File name  
  - Original URL  
  - File attachment  
  - Hash for deduplication  
- Send the original PDF to **Mistral OCR** for text recognition  
- Populate post content with OCR results  
- Convert **Markdown → HTML → Gutenberg blocks**  
- Provide post-level metadata, accessible in the editor sidebar  
- Expose single posts as **JSON endpoints**    

---

## Features  

- Registers two custom post types:  
  - **Import CPT** → stores the original Markdown data  
  - **Gutenberg CPT** → stores the processed Gutenberg version  
- Provides a sidebar settings panel with:  
  - Dashboard  
  - Logging  
  - Settings  
  - Single import  
  - Bulk import  
  - RSS feed view  
- Registers a custom taxonomy for workflow states:  
  - **Unprocessed**  
  - **Processed**  
  - **Human verified**  
  - **Staff verified**  

---

## Roadmap  

- Provide access for **remote WordPress sites** to collect processed data  
- Add functionality to **remove old PDFs** while retaining metadata (hash, date, filename)  
- Index pages for:  
  - All available processes + staff-verified content  
  - All available processes + unverified content  

---

## Thanks  

Thanks for checking out **pdf2p2** 
