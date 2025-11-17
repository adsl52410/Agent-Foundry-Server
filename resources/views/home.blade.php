<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Agent Foundry - Open-Source Toolbox and Plugin Ecosystem</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    
    <!-- Styles -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
                line-height: 1.6;
                color: #1b1b18;
                background-color: #FDFDFC;
            }
            .dark body {
                background-color: #0a0a0a;
                color: #EDEDEC;
            }
            .container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 2rem 1rem;
            }
            h1 {
                font-size: 3rem;
                font-weight: 700;
                margin-bottom: 1rem;
                line-height: 1.2;
            }
            h2 {
                font-size: 2rem;
                font-weight: 600;
                margin-top: 3rem;
                margin-bottom: 1rem;
            }
            h3 {
                font-size: 1.5rem;
                font-weight: 600;
                margin-top: 2rem;
                margin-bottom: 0.75rem;
            }
            p {
                margin-bottom: 1rem;
                font-size: 1.125rem;
            }
            ul, ol {
                margin-left: 1.5rem;
                margin-bottom: 1rem;
            }
            li {
                margin-bottom: 0.5rem;
            }
            code {
                background-color: #f4f4f4;
                padding: 0.2rem 0.4rem;
                border-radius: 0.25rem;
                font-family: ui-monospace, monospace;
                font-size: 0.9em;
            }
            .dark code {
                background-color: #1a1a1a;
            }
            pre {
                background-color: #f4f4f4;
                padding: 1rem;
                border-radius: 0.5rem;
                overflow-x: auto;
                margin: 1rem 0;
            }
            .dark pre {
                background-color: #1a1a1a;
            }
            pre code {
                background: none;
                padding: 0;
            }
            blockquote {
                border-left: 4px solid #f53003;
                padding-left: 1rem;
                margin: 1rem 0;
                font-style: italic;
            }
            .dark blockquote {
                border-left-color: #FF4433;
            }
            a {
                color: #f53003;
                text-decoration: underline;
            }
            .dark a {
                color: #FF4433;
            }
            a:hover {
                text-decoration: none;
            }
            .hero {
                text-align: center;
                padding: 4rem 0;
                border-bottom: 1px solid #e3e3e0;
            }
            .dark .hero {
                border-bottom-color: #3E3E3A;
            }
            .section {
                margin: 3rem 0;
            }
            .feature-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1.5rem;
                margin: 2rem 0;
            }
            .feature-card {
                background: white;
                padding: 1.5rem;
                border-radius: 0.5rem;
                border: 1px solid #e3e3e0;
            }
            .dark .feature-card {
                background: #161615;
                border-color: #3E3E3A;
            }
            .emoji {
                font-size: 1.5rem;
                margin-right: 0.5rem;
            }
            .github-button {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.75rem 1.5rem;
                background-color: #1b1b18;
                color: white;
                text-decoration: none;
                border-radius: 0.5rem;
                font-weight: 500;
                margin-top: 1.5rem;
                transition: background-color 0.2s;
            }
            .github-button:hover {
                background-color: #2a2a27;
                text-decoration: none;
            }
            .dark .github-button {
                background-color: #EDEDEC;
                color: #1b1b18;
            }
            .dark .github-button:hover {
                background-color: white;
            }
            .github-icon {
                width: 20px;
                height: 20px;
                fill: currentColor;
            }
        </style>
    @endif
</head>
<body>
    <div class="container">
        <div class="hero">
            <h1># Agent Foundry</h1>
            <p style="font-size: 1.25rem; max-width: 800px; margin: 0 auto;">
                We believe the real power of LLMs isn't just in chatting — it's in the <strong>tools</strong> they can use.
            </p>
            <p style="font-size: 1.125rem; max-width: 800px; margin: 1rem auto 0;">
                Agent Foundry is an <strong>open-source toolbox and plugin ecosystem</strong> for building, sharing, and combining tools that make AI useful in the real world. Think of it as a forge 🔨 where the community co-creates tools, versions them, and makes sure everything is reproducible.
            </p>
            <a href="https://github.com/adsl52410/Agent-Foundry" target="_blank" rel="noopener noreferrer" class="github-button">
                <svg class="github-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                </svg>
                View on GitHub
            </a>
        </div>

        <div class="section">
            <h2>🌟 Vision</h2>
            <ul>
                <li><strong>Tools-first AI</strong> — LLMs become powerful when they can call tools to act.</li>
                <li><strong>Community-built</strong> — anyone can create new plugins (OCR, screenshots, window control, AI analysis, etc.) and share them.</li>
                <li><strong>Reproducible & governed</strong> — plugins come with versioning, release channels (stable/beta/canary), and lockfiles to ensure consistent results.</li>
                <li><strong>Composable pipelines</strong> — mix and match tools into repeatable workflows, either programmatically or declaratively.</li>
            </ul>
        </div>

        <div class="section">
            <h2>⚙️ Core Features</h2>
            <div class="feature-grid">
                <div class="feature-card">
                    <h3>🔌 Plugin System</h3>
                    <p>Standardized interfaces for AI, OCR, window, screenshot, and more.</p>
                </div>
                <div class="feature-card">
                    <h3>📦 Remote Registry</h3>
                    <p>File-system based registry (default: <code>~/Desktop/af-registry/</code>) with version management and <code>index.json</code>.</p>
                </div>
                <div class="feature-card">
                    <h3>📥 Plugin Management</h3>
                    <p>Install, update, and publish plugins via CLI with automatic version resolution.</p>
                </div>
                <div class="feature-card">
                    <h3>🔒 Lockfiles</h3>
                    <p>Guarantee reproducibility across machines and teams.</p>
                </div>
                <div class="feature-card">
                    <h3>🛠 CLI</h3>
                    <p>Comprehensive command-line interface for plugin lifecycle management.</p>
                </div>
                <div class="feature-card">
                    <h3>🚀 Pipeline Execution</h3>
                    <p>Run plugins individually or compose them into workflows.</p>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>⚡ Quick Start</h2>
            
            <h3>Environment Setup</h3>
            <pre><code># Create and activate virtual environment
python3 -m venv venv
source venv/bin/activate  # Windows: venv\Scripts\activate

# Install dependencies
pip install -r requirements.txt</code></pre>

            <h3>Basic Usage</h3>
            <pre><code># Activate virtual environment (before each use)
source venv/bin/activate

# Publish plugin to remote registry (Desktop folder)
python3 -m afm.cli publish hello_world

# View available plugins in remote registry
python3 -m afm.cli remote-list

# Install plugin from remote
python3 -m afm.cli install hello_world

# List installed plugins
python3 -m afm.cli list

# Run plugin
python3 -m afm.cli run hello_world --args "your parameters"

# Update plugin to latest version
python3 -m afm.cli update hello_world

# Generate lockfile
python3 -m afm.cli lock</code></pre>

            <blockquote>
                <strong>Note</strong>: Remote registry default location is <code>~/Desktop/af-registry/</code>, can be modified in <code>afm/config/settings.py</code>.
            </blockquote>
        </div>

        <div class="section">
            <h2>🔌 Example Plugin: <code>ocr.tesseract</code></h2>
            <p>Here's how a plugin looks in Agent Foundry.</p>
            <p>Each plugin just needs to follow a standard <strong>interface (Protocol)</strong> and return a consistent result format.</p>

            <h3>1. Implement the interface</h3>
            <pre><code># agent_foundry_ocr_tesseract/ocr_plugin.py

from agent_foundry.interfaces import OCRService, Result
import pytesseract
from PIL import Image

class TesseractOCR(OCRService):
    def initialize(self, config: dict) -> bool:
        return True  # load configs if needed

    def extract_text(self, image_path: str) -> Result:
        try:
            text = pytesseract.image_to_string(Image.open(image_path))
            return {"success": True, "data": {"text": text}, "meta": {"engine": "tesseract"}}
        except Exception as e:
            return {"success": False, "error": {"code": "OCRFailed", "message": str(e)}}</code></pre>

            <h3>2. Register it as a plugin</h3>
            <pre><code># pyproject.toml

[project.entry-points."agent_foundry.plugins"]
"ocr.tesseract" = "agent_foundry_ocr_tesseract.ocr_plugin:TesseractOCR"</code></pre>

            <h3>3. Add metadata for the registry</h3>
            <p><code>meta.json</code></p>
            <pre><code>{
  "name": "agent_foundry_ocr_tesseract",
  "version": "0.4.2",
  "core": ">=0.3,<0.4",
  "apis": ["OCRService@1"],
  "description": "OCR plugin using Tesseract"
}</code></pre>

            <p><code>checksums.txt</code></p>
            <pre><code>sha256  agent_foundry_ocr_tesseract-0.4.2-py3-none-any.whl  a7d2...9f</code></pre>

            <h3>4. Publish to the registry</h3>
            <p>Use CLI to upload plugin to remote registry:</p>
            <pre><code># Publish plugin (automatically reads version from manifest.json)
python3 -m afm.cli publish ocr.tesseract

# Or specify version
python3 -m afm.cli publish ocr.tesseract --version 0.4.2</code></pre>
            <p>Plugin will be automatically uploaded to <code>~/Desktop/af-registry/plugins/ocr.tesseract/0.4.2/</code> and <code>index.json</code> will be updated.</p>

            <h3>5. Install and use the plugin</h3>
            <pre><code># Install from remote
python3 -m afm.cli install ocr.tesseract

# Or install specific version
python3 -m afm.cli install ocr.tesseract --version 0.4.2

# Run plugin
python3 -m afm.cli run ocr.tesseract --args '{"image_path": "sample.png"}'</code></pre>

            <h3>6. Plugin Registry Structure</h3>
            <p>Remote registry structure (default at <code>~/Desktop/af-registry/</code>):</p>
            <pre><code>af-registry/
├── index.json              # Plugin index, records all available plugins and versions
└── plugins/
    └── {plugin_name}/
        └── {version}/
            ├── plugin.py
            └── manifest.json</code></pre>
            <p>Locally installed plugins are located at <code>afm/plugins/{plugin_name}/</code>, registry information is in <code>data/registry.json</code>.</p>
        </div>

        <div class="section">
            <h2>📚 CLI Command Reference</h2>
            
            <h3>Plugin Management</h3>
            <ul>
                <li><code>install &lt;name&gt; [--version VERSION]</code> - Install plugin from remote registry (automatically uses latest version if not specified)</li>
                <li><code>list</code> - List installed plugins</li>
                <li><code>uninstall &lt;name&gt;</code> - Uninstall plugin</li>
                <li><code>update &lt;name&gt; [--version VERSION]</code> - Update plugin (automatically checks and updates to latest version if not specified)</li>
                <li><code>run &lt;name&gt; [--args ARGS]</code> - Run plugin</li>
            </ul>

            <h3>Registry Operations</h3>
            <ul>
                <li><code>publish &lt;name&gt; [--version VERSION]</code> - Upload local plugin to remote registry</li>
                <li><code>remote-list</code> - List all available plugins in remote registry</li>
                <li><code>lock</code> - Regenerate lockfile (fix exact versions of all current plugins)</li>
            </ul>

            <h3>Examples</h3>
            <pre><code># Complete workflow
python3 -m afm.cli publish my_plugin          # Publish plugin
python3 -m afm.cli remote-list                # View remote plugins
python3 -m afm.cli install my_plugin          # Install plugin
python3 -m afm.cli list                        # View installed
python3 -m afm.cli run my_plugin --args "test" # Run plugin
python3 -m afm.cli update my_plugin            # Update to latest version
python3 -m afm.cli lock                        # Generate lockfile</code></pre>
        </div>

        <div class="section">
            <h2>🤝 How to Contribute</h2>
            <p>Agent Foundry is meant to be <strong>built together</strong>. You can help by:</p>
            <ol>
                <li>Submitting new plugins (OCR, AI adapters, integrations).</li>
                <li>Writing docs, guides, or examples.</li>
                <li>Improving testing, CI/CD, and conformance checks.</li>
                <li>Sharing ideas and feedback in issues/discussions.</li>
            </ol>
            <p>👉 See <code>CONTRIBUTING.md</code> for setup steps and development guidelines.</p>
        </div>

        <div class="section">
            <h2>📦 Parameters and I/O Specification</h2>
            <p>Agent Foundry plugins and pipelines follow a consistent contract for inputs and outputs to enable composition, testing, and reproducibility.</p>

            <h3>Parameters</h3>
            <ul>
                <li><strong>Format:</strong> JSON object (UTF-8)</li>
                <li><strong>Validation:</strong> JSON Schema (Draft 7+) or Pydantic models (recommended in Python)</li>
                <li><strong>Versioning:</strong> Schemas should be versioned alongside the plugin (e.g., <code>OCRService@1</code>)</li>
            </ul>

            <p>Example schema (JSON Schema):</p>
            <pre><code>{
  "$schema": "https://json-schema.org/draft-07/schema#",
  "$id": "https://agent-foundry.dev/schemas/ocr.extract_text@1.json",
  "title": "OCR.extract_text parameters",
  "type": "object",
  "required": ["image_path"],
  "properties": {
    "image_path": { "type": "string" },
    "lang": { "type": "string", "default": "eng" },
    "dpi": { "type": "integer", "minimum": 72, "maximum": 1200 }
  },
  "additionalProperties": false
}</code></pre>

            <p>Recommended validation flow:</p>
            <p>1) Load JSON params → 2) Validate against schema → 3) Pass typed object to implementation.</p>

            <h3>Standard Output/Errors/Exit Code</h3>
            <ul>
                <li><strong>stdout:</strong> Structured JSON result on success</li>
                <li><strong>stderr:</strong> Human-readable logs, warnings, and error diagnostics</li>
                <li><strong>exit code:</strong> <code>0</code> for success; non-zero for failure (e.g., <code>2</code> for validation error, <code>3</code> for runtime error)</li>
            </ul>

            <p>Success payload shape:</p>
            <pre><code>{
  "success": true,
  "data": { /* task-specific result */ },
  "meta": { "plugin": "ocr.tesseract", "version": "0.4.2", "elapsed_ms": 123 }
}</code></pre>

            <p>Error payload shape (written to stdout for machine consumption, details to stderr):</p>
            <pre><code>{
  "success": false,
  "error": {
    "code": "ValidationError",
    "message": "'image_path' is required",
    "details": { "path": ["image_path"], "schema": "ocr.extract_text@1" }
  },
  "meta": { "plugin": "ocr.tesseract", "version": "0.4.2" }
}</code></pre>

            <p>Suggested exit codes:</p>
            <ul>
                <li><code>2</code>: Parameter/Schema validation error</li>
                <li><code>3</code>: Dependency or environment error (e.g., missing binary/model)</li>
                <li><code>4</code>: External I/O failure (network/filesystem)</li>
                <li><code>5</code>: Plugin-defined runtime error</li>
            </ul>
        </div>

        <div class="section">
            <h2>🗺 Roadmap</h2>
            <ul>
                <li><strong>M1</strong>: Core skeleton (interfaces, container, CLI, file-registry driver, lock system).</li>
                <li><strong>M2</strong>: Plugin ecosystem (AI/OCR/Window/Screenshot as separate packages, lock + verify + checksum).</li>
                <li><strong>M3</strong>: Docs & conformance tests (PLUGIN_GUIDE, VERSIONING, SECURITY, CI).</li>
                <li><strong>M4</strong>: Declarative YAML pipelines, multi-version coexistence, optional signing.</li>
            </ul>
        </div>

        <div class="section">
            <h2>📜 License</h2>
            <p>MIT — free to use, share, and modify.</p>
        </div>

        <div class="section" style="text-align: center; padding: 3rem 0; border-top: 1px solid #e3e3e0;">
            <p style="font-size: 1.25rem; font-weight: 600;">
                ✨ Agent Foundry is not just another framework — it's a <strong>community forge for AI tools</strong>.
            </p>
            <p style="font-size: 1.125rem; margin-top: 1rem;">
                Let's build the toolbox that makes LLMs truly useful.
            </p>
            <a href="https://github.com/adsl52410/Agent-Foundry" target="_blank" rel="noopener noreferrer" class="github-button" style="margin-top: 2rem;">
                <svg class="github-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                </svg>
                View on GitHub
            </a>
        </div>
    </div>
</body>
</html>

