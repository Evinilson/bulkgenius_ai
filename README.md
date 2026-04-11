# BulkGenius AI — PrestaShop AI Bulk Product Importer

<div align="center">

![BulkGenius AI](https://img.shields.io/badge/BulkGenius-AI-6366f1?style=for-the-badge&logo=sparkles&logoColor=white)
![PrestaShop](https://img.shields.io/badge/PrestaShop-8.x-DF0067?style=for-the-badge&logo=prestashop&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-22c55e?style=for-the-badge)

**The first PrestaShop bulk importer powered by AI.**
Import hundreds of products from Excel and generate complete SEO content — descriptions, meta tags, and keywords — in one click.

[Features](#-features) · [Demo](#-how-it-works) · [Installation](#-installation) · [Supported AI Providers](#-supported-ai-providers) · [Roadmap](#-roadmap)

</div>

---

## 🚀 The Problem It Solves

Creating product listings manually is incredibly slow and tedious. Entering **100 products** manually can take:

- ⏱️ **2 days or more** of focused manual work
- ✍️ Writing unique descriptions for each one
- 🔍 Optimising SEO fields individually
- 🏷️ Researching and adding relevant tags

**BulkGenius AI reduces this to under 1 hour.**

---

## ✨ Features

- 📁 **Excel/CSV Import** — Upload `.xlsx`, `.xls`, or `.csv` files with your product data
- 🤖 **AI Content Generation** — Automatically generates for each product:
  - Long HTML description (150+ words, structured with `<p>`, `<ul>`, `<strong>`)
  - Short SEO description (max 160 chars)
  - Meta title (max 60 chars)
  - Meta description (max 155 chars)
  - 5–8 relevant keywords/tags
- 🔄 **Multi-Provider Support** — Works with OpenAI, Google Gemini, and Groq
- 🆓 **Free AI Options** — Use Google Gemini or Groq at no cost for testing
- 🌍 **Multilingual** — Generate content in Portuguese or English
- ✅ **Bulk Creation** — Creates all products directly in PrestaShop with one click
- 📊 **Live Results** — See which products succeeded or failed in real time

---

## 🎬 How It Works

```
1. Prepare your Excel file with: Name | Reference | Price | Description
        ↓
2. Upload the file in the BulkGenius AI admin panel
        ↓
3. Preview your products before importing
        ↓
4. Click "Import with AI" — the module sends each product to the AI
        ↓
5. AI generates full SEO content for every product
        ↓
6. Products are created in PrestaShop automatically
```

### Excel Format

The first row must contain column headers (Portuguese or English accepted):

| Nome / Name | Referência / Reference | Preço / Price | Descrição / Description |
|---|---|---|---|
| Smartwatch Ultra Z | SW-001 | 49.90 | Smartwatch with heart rate monitor and GPS |
| Bluetooth Headphones | AB-202 | 129.00 | Premium headphones with noise cancellation |

---

## 🤖 Supported AI Providers

| Provider | Free Tier | Best For | Model |
|---|---|---|---|
| **Google Gemini** | ✅ 1500 req/day | Testing & production | `gemini-1.5-flash` |
| **Groq** | ✅ Daily limits | Ultra-fast generation | `llama-3.1-8b-instant` |
| **OpenAI** | ❌ Paid (~$0.001/product) | Highest quality | `gpt-4o-mini` |

> 💡 **Tip:** Start with Gemini or Groq for free testing. Switch to OpenAI for production if you need higher quality output.

---

## 📦 Installation

### Requirements

- PrestaShop **8.x**
- PHP **8.1+**
- Composer
- An API key from at least one supported AI provider

### Steps

**1. Clone or download the module**
```bash
git clone https://github.com/evinilson/bulkgenius-ai.git
```

**2. Install PHP dependencies**
```bash
cd bulkgenius-ai
composer install --no-dev
```

**3. Copy to your PrestaShop modules folder**
```bash
cp -r bulkgenius-ai/ /path/to/prestashop/modules/bulkgenius_ai/
```

**4. Install via PrestaShop Back Office**

Go to **Modules → Module Manager**, search for **BulkGenius AI** and click **Install**.

**5. Configure your AI provider**

Go to **Catalogue → BulkGenius AI** and fill in:
- Select your AI provider (Gemini, Groq, or OpenAI)
- Enter your API key
- Choose the language for generated content
- Select the default category for imported products

### Getting Free API Keys

| Provider | URL |
|---|---|
| Google Gemini | [aistudio.google.com](https://aistudio.google.com) |
| Groq | [console.groq.com](https://console.groq.com) |
| OpenAI | [platform.openai.com](https://platform.openai.com) |

---

## 🏗️ Architecture

```
bulkgenius_ai/
├── bulkgenius_ai.php                    # Module entry point
├── composer.json                         # Dependencies (phpspreadsheet)
├── controllers/
│   └── admin/
│       └── AdminBulkGeniusAiController.php
├── classes/
│   ├── AiServiceInterface.php            # Contract for all AI providers
│   ├── AbstractAiService.php             # Shared logic (prompt, JSON parsing)
│   ├── OpenAiService.php                 # OpenAI implementation
│   ├── GeminiService.php                 # Google Gemini implementation
│   ├── GroqService.php                   # Groq implementation
│   ├── AiServiceFactory.php              # Factory pattern — picks the right provider
│   ├── ExcelReader.php                   # Reads and validates Excel/CSV files
│   └── ProductCreator.php               # Creates products via PrestaShop API
└── views/
    ├── templates/admin/
    │   └── main.tpl                      # Smarty admin template
    ├── css/admin.css
    └── js/admin.js
```

The AI layer follows a clean **Interface → Abstract → Implementation** pattern, making it easy to add new providers (e.g., Anthropic Claude, Mistral) without changing any existing code.

---

## 🗺️ Roadmap

- [x] Excel/CSV bulk import
- [x] AI content generation (descriptions, SEO, tags)
- [x] Multi-provider support (OpenAI, Gemini, Groq)
- [x] Multilingual output (PT / EN)
- [ ] **AI content editor** — Regenerate content for individual products directly from the product page
- [ ] Image generation via AI (DALL·E, Imagen)
- [ ] Per-product category assignment from Excel
- [ ] Import progress bar (product by product)
- [ ] WooCommerce support
- [ ] PrestaShop Addons Marketplace release

---

## 🤝 Contributing

Contributions are welcome! If you find a bug or have a feature idea:

1. Fork the repository
2. Create a branch: `git checkout -b feature/your-feature`
3. Commit your changes: `git commit -m 'Add your feature'`
4. Push: `git push origin feature/your-feature`
5. Open a Pull Request

---

## 📄 License

MIT License — see [LICENSE](LICENSE) for details.

---

## 👤 Author

Built by **Evinilson Fernandes** — Full Stack Developer from Portugal 🇵🇹

- GitHub: [@Evinilson](https://github.com/Evinilson)
- LinkedIn: [linkedin.com/in/evinilson-fernandes](https://linkedin.com/in/evinilson-fernandes)
- WebSummit 2025: Find me there! 👋

---

<div align="center">

**If this project helped you, give it a ⭐ on GitHub!**

*Built with AI, for the AI era of e-commerce.*

</div>
