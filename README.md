# Ecommerce AI Assistant (`rasel673/ecommerce-ai-assistant`)

[![Latest Stable Version](https://img.shields.io/packagist/v/rasel673/ecommerce-ai-assistant.svg?style=flat-square)](https://packagist.org/packages/rasel673/ecommerce-ai-assistant)
[![Total Downloads](https://img.shields.io/packagist/dt/rasel673/ecommerce-ai-assistant.svg?style=flat-square)](https://packagist.org/packages/rasel673/ecommerce-ai-assistant)
[![License](https://img.shields.io/packagist/l/rasel673/ecommerce-ai-assistant.svg?style=flat-square)](LICENSE)

A reusable, schema-agnostic AI chatbot and analytics agent package for Laravel ecommerce applications.

---

## Key Features

* **LLM Provider Abstraction:** Built on `AIProviderInterface` with an out-of-the-box OpenAI Provider using Laravel's HTTP Client.
* **Autonomous Tool Calling Agent:** The AI dynamically identifies and executes ecommerce analytics tools to answer merchant questions.
* **Schema-Agnostic Ecommerce Adapter:** Connects to any Laravel ecommerce database schema (e.g., `orders`, `order_details`, `qty_available`, `stock`) without modifying core package code.
* **Automatic Adapter Generator:** Install the package and run `php artisan ecommerce-ai:install` or `php artisan make:ecommerce-adapter` to instantly generate an application adapter.
* **5 Normalized Analytics Tools:**
  1. `get_sales_summary`: Period & custom date range sales metrics.
  2. `get_order_summary`: Order volume, AOV, and status distribution.
  3. `get_top_products`: Ranking bestsellers by sales volume and revenue.
  4. `get_low_stock_products`: Low-stock threshold alerts to prevent stockouts.
  5. `get_product_stock`: Real-time inventory and price lookup by name or SKU.
* **Security & Multi-Tenancy:**
  * Zero arbitrary SQL execution; all queries are application-controlled via parameterized query builders.
  * Store and tenant isolation (`store_id`, `tenant_id`).
  * Read-only database operations.
  * API credentials are never leaked in logs, exceptions, or client payloads.
* **Drop-in Admin Chatbot Widget:** Responsive Blade widget styled with a modern purple accent (`#7c3aed`), loading animations, XSS-safe message rendering, and collapsible drawer.

---

## System Requirements

* **PHP:** `^8.2`
* **Laravel Framework:** `^9.0 | ^10.0 | ^11.0 | ^12.0`
* **OpenAI API Key**

---

## Directory Structure

```text
ecommerce-ai-assistant/
|-- config/
|   \-- ecommerce-ai.php                  # Package configuration file
|-- resources/
|   \-- views/
|       \-- chatbot.blade.php             # Reusable Admin Chatbot UI widget
|-- routes/
|   \-- api.php                           # Package API routes (POST /api/ecommerce-ai/chat)
|-- src/
|   |-- Adapters/
|   |   \-- GenericEcommerceAdapter.php   # Default database query adapter
|   |-- Agent/
|   |   \-- AgentRunner.php               # Autonomous tool-calling execution loop
|   |-- Console/
|   |   \-- Commands/
|   |       |-- InstallEcommerceAICommand.php    # php artisan ecommerce-ai:install
|   |       \-- MakeEcommerceAdapterCommand.php  # php artisan make:ecommerce-adapter
|   |-- Contracts/
|   |   |-- AIProviderInterface.php       # AI provider abstraction contract
|   |   |-- EcommerceAdapterInterface.php # Normalized ecommerce data contract
|   |   \-- ToolInterface.php             # AI Tool definition contract
|   |-- Exceptions/
|   |   \-- AIProviderException.php       # Safe exception handling without secret leakage
|   |-- Http/
|   |   \-- Controllers/
|   |       \-- ChatController.php        # Chat API Controller
|   |-- Providers/
|   |   \-- OpenAIProvider.php            # OpenAI API client implementation
|   |-- Registry/
|   |   \-- ToolRegistry.php              # Analytics tool discovery and schema registry
|   |-- Support/
|   |   \-- DateRangeResolver.php         # Date filter & range boundary parser
|   |-- Tools/
|   |   |-- LowStockTool.php              # get_low_stock_products tool
|   |   |-- OrderSummaryTool.php          # get_order_summary tool
|   |   |-- ProductStockTool.php          # get_product_stock tool
|   |   |-- SalesTool.php                 # get_sales_summary tool
|   |   \-- TopProductsTool.php           # get_top_products tool
|   \-- EcommerceAIServiceProvider.php    # Main Laravel Service Provider
|-- stubs/
|   \-- ecommerce-adapter.stub            # Generator stub for custom adapter
|-- tests/
|   |-- Feature/
|   |   |-- AgentToolCallingTest.php      # End-to-end tool calling tests
|   |   |-- ChatApiTest.php               # Chat API endpoint tests
|   |   |-- ChatbotViewTest.php           # Blade widget rendering tests
|   |   |-- ConsoleCommandsTest.php       # Artisan generator command tests
|   |   \-- PackageFoundationTest.php     # Provider & configuration tests
|   |-- Unit/
|   |   |-- AnalyticsToolsTest.php        # Analytics tools unit tests
|   |   |-- EcommerceAdapterTest.php      # Adapter contract & query tests
|   |   |-- OpenAIProviderTest.php        # OpenAI integration tests with Http::fake
|   |   \-- SalesToolTest.php             # Sales tool analytics tests
|   \-- TestCase.php                      # Base Orchestra Testbench test case
|-- composer.json                         # Package definitions, PSR-4 & discovery
|-- phpunit.xml.dist                      # PHPUnit testing configuration
|-- LICENSE                               # MIT License
|-- .gitignore                            # Git ignore file
\-- README.md                             # Documentation
```

---

## Supported AI Providers

The package supports 7 AI providers out of the box through `AIProviderFactory`:

| Provider | Driver Key | Default Model | Environment Variables |
| :--- | :--- | :--- | :--- |
| **OpenAI** | `openai` | `gpt-4o-mini` | `OPENAI_API_KEY`, `OPENAI_MODEL` |
| **Anthropic Claude** | `anthropic` or `claude` | `claude-3-5-haiku-20241022` | `ANTHROPIC_API_KEY`, `ANTHROPIC_MODEL` |
| **Google Gemini** | `gemini` or `google` | `gemini-1.5-flash` | `GEMINI_API_KEY`, `GEMINI_MODEL` |
| **Groq** | `groq` | `llama-3.3-70b-versatile` | `GROQ_API_KEY`, `GROQ_MODEL` |
| **DeepSeek** | `deepseek` | `deepseek-chat` | `DEEPSEEK_API_KEY`, `DEEPSEEK_MODEL` |
| **Ollama (Local)** | `ollama` | `llama3.2` | `OLLAMA_BASE_URL` (default: `http://localhost:11434/v1`) |
| **Mistral AI** | `mistral` | `mistral-small-latest` | `MISTRAL_API_KEY`, `MISTRAL_MODEL` |

### Selecting the Provider:
In your `.env`:
```env
# Choose: openai, anthropic, gemini, groq, deepseek, ollama, mistral
ECOMMERCE_AI_PROVIDER=gemini

# Provider credentials
GEMINI_API_KEY=your-gemini-api-key
```

All providers automatically support the 5 ecommerce analytics tools with autonomous tool calling!

---

## Installation

### 1. Require Package
```bash
composer require rasel673/ecommerce-ai-assistant
```

### 2. Run Package Installer
```bash
php artisan ecommerce-ai:install
```
This automatically:
* Publishes `config/ecommerce-ai.php`.
* Generates your application adapter at `app/Adapters/AppEcommerceAdapter.php`.

### 3. Configure Environment (`.env`)
```env
OPENAI_API_KEY=sk-your-openai-api-key
OPENAI_MODEL=gpt-4o-mini
ECOMMERCE_AI_ADAPTER='App\Adapters\AppEcommerceAdapter'
```

---

## Custom Ecommerce Adapter Development

If your application uses custom table or column names (e.g. `order_details`, `qty_available`), implement `EcommerceAdapterInterface`:

```php
namespace App\Adapters;

use Rasel673\EcommerceAI\Contracts\EcommerceAdapterInterface;
use Illuminate\Support\Facades\DB;

class CustomStoreAdapter implements EcommerceAdapterInterface
{
    public function getSalesSummary(array $filters = []): array
    {
        // Custom query
    }

    public function getOrderSummary(array $filters = []): array
    {
        // Custom query
    }

    public function getTopSellingProducts(int $limit = 5, array $filters = []): array
    {
        // Custom query
    }

    public function getLowStockProducts(int $threshold = 10, int $limit = 10, array $filters = []): array
    {
        // Custom query
    }

    public function getProductStock(string $searchTerm, array $filters = []): array
    {
        // Custom query
    }
}
```

Then register it in `.env`:
```env
ECOMMERCE_AI_ADAPTER='App\Adapters\CustomStoreAdapter'
```

---

## Admin Chatbot UI Widget

To add the floating chatbot widget to your admin dashboard, place the Blade directive in your admin layout before `</body>`:

```blade
@include('ecommerce-ai::chatbot')
```

The widget automatically inherits your configured API prefix and handles CSRF tokens out of the box.

---

## Running Automated Tests

```bash
vendor/bin/phpunit
```

---

## Packagist Distribution Guide

### Step 1: Push to GitHub
```bash
git init
git add .
git commit -m "feat: complete ecommerce ai assistant package foundation and tools"
git remote add origin https://github.com/rasel673/ecommerce-ai-assistant.git
git branch -M main
git push -u origin main
```

### Step 2: Tag a Release (Semantic Versioning)
```bash
git tag -a v1.0.0 -m "Release v1.0.0: Initial stable release"
git push origin v1.0.0
```

### Step 3: Submit to Packagist
1. Visit [Packagist.org](https://packagist.org/) and log in.
2. Click **Submit**.
3. Enter repository URL: `https://github.com/rasel673/ecommerce-ai-assistant`.
4. Click **Check** and confirm submission.
5. Setup the GitHub Webhook under your repository settings (`https://packagist.org/api/github?username=rasel673`) to enable auto-updates on git push.

---

## License

This package is open-source software licensed under the [MIT license](LICENSE).
