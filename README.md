# Extrator de Tábuas de Marés (CHM Tide Reader)

[🇺🇸 English Version](#english-version)

Este projeto tem como objetivo coletar e estruturar os dados de tábuas de marés do litoral brasileiro, disponibilizados em formato PDF pelo Centro de Hidrografia da Marinha do Brasil (CHM) através do link: [https://www.marinha.mil.br/chm/dados-do-segnav/dados-de-mare-mapa](https://www.marinha.mil.br/chm/dados-do-segnav/dados-de-mare-mapa). 

O sistema extrai as informações dos documentos e as persiste em um banco de dados **PostgreSQL**, preparando o terreno para que outra aplicação (ou serviço de API) possa consultar e expor estes dados de forma ágil e padronizada.

## 🛠️ Tecnologias Utilizadas

Este projeto foi construído utilizando práticas modernas de desenvolvimento em PHP e aproveitando extensões poderosas de banco de dados:

* **PHP 8.5**: Linguagem principal do projeto.
* **PostgreSQL & PostGIS**: Banco de dados relacional escolhido para armazenamento. A extensão PostGIS é fundamental aqui para tratar as coordenadas geográficas (tipo `point`) das localidades de maré de forma nativa.
* **Smalot/PdfParser (`smalot/pdfparser`)**: Biblioteca robusta utilizada para fazer a leitura e extração de textos dos PDFs gerados pela Marinha.
* **Phinx (`robmorgan/phinx`)**: Ferramenta de migração de banco de dados, responsável por criar a estrutura das tabelas (`locations`, tábuas, etc.) utilizando UUIDs e tipos geográficos.
* **Componentes Symfony**:
  * `symfony/dotenv`: Para o gerenciamento de configurações do ambiente.
  * `symfony/dependency-injection` e `symfony/config`: Para estruturar a aplicação de forma desacoplada através de Injeção de Dependências.

## ✅ Cobertura de Testes

A qualidade do código é uma prioridade neste projeto, garantindo que o parser lide corretamente com o formato complexo dos PDFs e que a inserção no banco seja precisa. 

Utilizamos o **PHPUnit 13.1** e a cobertura de código é extensa:
- **Domínios e Entidades** (`Tide`, `Month`, `Collection`): **100%** de cobertura.
- **Serviços** (`PdfParser`, `LocationExtractor`, `TideStore`): **100%** de cobertura, garantindo que a lógica de extração funcione em todos os cenários conhecidos.
- **Repositórios** (`Tide`, `Location`): Totalmente cobertos com testes de integração (~95-100%) para atestar o salvamento correto no PostGIS e uso do PDO.
- **Comandos (`ParseAll`, `ParseOne`)**: **100%** de cobertura da interface de linha de comando.

## 🚀 Como Configurar e Executar

1. **Clone o repositório e instale as dependências:**
   ```bash
   composer install
   ```

2. **Configuração de Ambiente:**
   Copie o arquivo de exemplo e edite as credenciais do seu banco de dados PostgreSQL.
   ```bash
   cp .env.example .env
   ```

3. **Migrações do Banco de Dados:**
   Certifique-se de que seu banco PostgreSQL tenha a extensão `uuid-ossp` e `postgis` habilitadas, e então rode:
   ```bash
   vendor/bin/phinx migrate
   ```

4. **Executando o Parser:**
   O projeto conta com comandos de console construídos com Symfony Console para facilitar a extração dos dados.
   
   Para processar um único PDF:
   ```bash
   bin/console tide:parse 2026 ./resources/tide-pdf/2026/arquivo.pdf
   ```

   Para processar todos os PDFs de um determinado ano listados no diretório configurado:
   ```bash
   bin/console tide:parse-all 2026
   ```

5. **Rodando os Testes:**
   ```bash
   vendor/bin/phpunit
   ```
   *(Para ver a cobertura detalhada, use `vendor/bin/phpunit --coverage-text`)*

---

<a id="english-version"></a>
# Brazilian Tide Tables Extractor (CHM Tide Reader)

This project aims to collect and structure Brazilian tide table data, which is provided in PDF format by the Brazilian Navy's Hydrography Center (CHM) via the link: [https://www.marinha.mil.br/chm/dados-do-segnav/dados-de-mare-mapa](https://www.marinha.mil.br/chm/dados-do-segnav/dados-de-mare-mapa).

The system extracts information from these documents and persists it into a **PostgreSQL** database. This establishes the foundation so another application (or API service) can easily query and expose this data.

## 🛠️ Technology Stack

This project was built using modern PHP development practices and leveraging powerful database extensions:

* **PHP 8.5**: Core language.
* **PostgreSQL & PostGIS**: Relational database chosen for storage. The PostGIS extension is crucial here for natively handling geographic coordinates (`point` type) for tide locations.
* **Smalot/PdfParser (`smalot/pdfparser`)**: Robust library used to parse and extract text from the official PDFs provided by the Navy.
* **Phinx (`robmorgan/phinx`)**: Database migration tool, responsible for creating the table structures (using UUIDs and geospatial types).
* **Symfony Components**: `dotenv`, `dependency-injection`, and `config` to ensure decoupled and maintainable code.

## ✅ Test Coverage

Code quality is a primary focus, ensuring the parser correctly handles complex PDF formatting and precise database insertions.

We use **PHPUnit 13.1** and the code coverage is extensive:
- **Domains and Entities** (`Tide`, `Month`, `Collection`): **100%** coverage.
- **Services** (`PdfParser`, `LocationExtractor`, `TideStore`): **100%** coverage, verifying extraction logic works under all known scenarios.
- **Repositories** (`Tide`, `Location`): Completely covered with integration tests (~95-100%) to ensure correct persistence via PDO and PostGIS.
- **Commands (`ParseAll`, `ParseOne`)**: **100%** coverage of the command-line interface.

## 🚀 Setup & Execution

1. **Install dependencies:**
   ```bash
   composer install
   ```

2. **Environment Setup:**
   Copy the example file and edit your PostgreSQL credentials.
   ```bash
   cp .env.example .env
   ```

3. **Database Migrations:**
   Ensure your PostgreSQL database has the `uuid-ossp` and `postgis` extensions enabled, then run:
   ```bash
   vendor/bin/phinx migrate
   ```

4. **Running the Parser:**
   The project includes console commands built with Symfony Console to easily extract the data.
   
   To parse a single PDF file:
   ```bash
   bin/console tide:parse 2026 ./resources/tide-pdf/2026/file.pdf
   ```

   To parse all PDFs for a specific year located in the configured directory:
   ```bash
   bin/console tide:parse-all 2026
   ```

5. **Running Tests:**
   ```bash
   vendor/bin/phpunit
   ```
