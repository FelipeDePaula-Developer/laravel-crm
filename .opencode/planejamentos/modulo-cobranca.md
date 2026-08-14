# Módulo de Cobrança — Plano de Desenvolvimento

> **Objetivo:** Criar um módulo desacoplado (`packages/Webkul/Billing/`) para gerenciar cobranças (boletos, carnês) e parcelas, seguindo os padrões arquiteturais do Krayin CRM.

---

## Estrutura do Módulo

```
packages/Webkul/Billing/
├── Config/
│   └── billing_statuses.php
├── Contracts/
│   ├── Billing.php
│   └── BillingInstallment.php
├── Database/Migrations/
│   ├── create_billing_installments_table.php
│   └── create_billings_table.php
├── Enums/
│   ├── BillingStatus.php
│   └── InstallmentStatus.php
├── Jobs/
│   └── ProcessBillingJob.php
├── Models/
│   ├── Billing.php
│   ├── BillingProxy.php
│   ├── BillingInstallment.php
│   └── BillingInstallmentProxy.php
├── Providers/
│   ├── BillingServiceProvider.php
│   └── ModuleServiceProvider.php
├── Repositories/
│   ├── BillingRepository.php
│   └── BillingInstallmentRepository.php
└── Services/
    ├── BillingImportService.php
    └── BillingProcessService.php
```

---

## Passo 1 — Enums de Status

**O que:** Criar os enums que representam os status possíveis de uma cobrança e de uma parcela.

**Arquivos:**
- `Enums/BillingStatus.php` (Pending, Paid, Overdue, Cancelled)
- `Enums/InstallmentStatus.php` (Pending, Paid, Overdue, Renegotiated, Cancelled)

**Conceitos para praticar:**
- **PHP 8.1+ Enums** — `enum BillingStatus: string { case Pending = 'pending'; ... }`
- **`->value`** — Acessa o valor string do case
- **`::from()`** — Converte string para Enum (lança erro se inválido)
- **`::tryFrom()`** — Converte string para Enum (retorna null se inválido)
- **Laravel `$casts`** — Cast automático: `'status' => BillingStatus::class`

---

## Passo 2 — Migrations

**O que:** Criar as tabelas `billing_installments` (pai) e `billings` (filho) no banco.

**Arquivos:**
- `Database/Migrations/create_billing_installments_table.php`
- `Database/Migrations/create_billings_table.php`

**Modelagem:**

| Tabela | Papel | Campos principais |
|--------|-------|-------------------|
| `billing_installments` | **Pai** — plano de parcelamento | id, person_id (FK → persons), number, original_value, delinquent_value, paid_value, interest_value, penalty_value, due_date, status |
| `billings` | **Filho** — boleto/cobrança individual | id, billing_installment_id (FK), installment_number, original_value, delinquent_value, paid_value, interest_value, penalty_value, due_date, paid_date, status |

**Conceitos para praticar:**
- **Anonymous Migration Class** — `return new class extends Migration { ... }` (padrão Krayin)
- **Blueprint** — `$table->id()`, `$table->decimal('original_value', 18, 2)`, `$table->string('status')`
- **Foreign Key** — `$table->foreignId('person_id')->constrained()->onDelete('restrict')`
- **`onDelete('restrict')`** — Impede deletar pessoa que possui cobranças
- **`loadMigrationsFrom()`** — No ServiceProvider, para registrar as migrations do módulo

---

## Passo 3 — Contracts (Interfaces)

**O que:** Criar as interfaces marker para cada model.

**Arquivos:**
- `Contracts/Billing.php` — `interface Billing {}`
- `Contracts/BillingInstallment.php` — `interface BillingInstallment {}`

**Conceitos para praticar:**
- **Interface Marker** — Interface vazia que serve como contrato. O model implementa, o container resolve.
- **Concord Model Resolution** — O container do Laravel usa a interface para encontrar a model concreta registrada via `ModuleServiceProvider`.

---

## Passo 4 — Models + Proxies

**O que:** Criar as Eloquent models, seus Proxies e registra-los no Concord.

**Arquivos:**
- `Models/Billing.php` + `Models/BillingProxy.php`
- `Models/BillingInstallment.php` + `Models/BillingInstallmentProxy.php`

**Conceitos para praticar:**
- **Eloquent Model** — Extends `Model`, implementa o Contract, usa traits como `HasFactory`
- **`$fillable`** — Define quais campos aceitam mass-assignment (nunca incluir `id`)
- **`$casts`** — Converte tipos automaticamente (`'status' => InstallmentStatus::class`, `'due_date' => 'date'`)
- **Relationships** — `belongsTo`, `hasMany` via `Proxy::modelClass()`
- **`PersonProxy::modelClass()`** — Sempre referenciar outras models via Proxy, nunca classe concreta
- **ModelProxy** — Extends `Konekt\Concord\Proxies\ModelProxy`, resolve a model real em runtime

---

## Passo 5 — Providers

**O que:** Criar os dois service providers do módulo e registrar no Concord.

**Arquivos:**
- `Providers/ModuleServiceProvider.php` — Lista as models para o Concord
- `Providers/BillingServiceProvider.php` — Boot de migrations, config, events

**Conceitos para praticar:**
- **`BaseModuleServiceProvider`** — Provider especial do Concord que registra models no container
- **`boot()` vs `register()`** — register = define bindings; boot = executa depois de todos registrarem
- **`mergeConfigFrom()`** — Junta arquivos de config do módulo com os do app
- **`loadMigrationsFrom()`** — Carrega as migrations do diretório do módulo
- **`config/concord.php`** — Array `modules[]` onde o provider deve ser adicionado

---

## Passo 6 — Repositories

**O que:** Criar repositories para acesso aos dados seguindo o padrão Prettus (Spring Data Repository do Java).

**Arquivos:**
- `Repositories/BillingRepository.php`
- `Repositories/BillingInstallmentRepository.php`

**Conceitos para praticar:**
- **Repository Pattern** — Abstrai queries. Controllers nunca chamam `Model::query()`, sempre passam pelo Repository
- **Prettus BaseRepository** — `makeModel()` resolve o Contract via container (similar ao Spring Data)
- **`model()` method** — Retorna o **Contract** (interface), não a classe concreta
- **`$fieldSearchable`** — Habilita filtro automático via query string (ex: `?filter[status]=pending`)
- **Auto-resolution** — Laravel injeta o Repository automaticamente via type-hint no constructor, sem bind manual

---

## Passo 7 — Services (Separação de Responsabilidades)

**O que:** Dois services com responsabilidades distintas, desacoplados entre si.

### Arquitetura

```
┌─────────────┐     ┌──────────────────┐     ┌─────────────┐
│  CSV Upload  │────▶│ BillingImportService│────▶│    Queue    │
└─────────────┘     └──────────────────┘     │ (Redis/RMQ) │
                                              └──────┬──────┘
                                                     │
                                                     ▼
                                            ┌──────────────────┐
                                            │BillingProcessService│
                                            │   (1..N workers)   │
                                            └──────────────────┘
```

### Service 1: `Services/BillingImportService.php`

**Responsabilidade:** Ler dados de fontes externas e enviar para a fila.

**Conceitos para praticar:**
- **Service Layer** — Encapsula lógica de leitura de dados fora do controller
- **Dependency Injection** — Recebe Repository via constructor
- **`Bus::dispatch(new ProcessBillingJob($data))`** — Envia job para a fila
- **Filosofia:** Este service NÃO cria registros no banco. Ele apenas lê o CSV e despacha jobs.

### Service 2: `Services/BillingProcessService.php`

**Responsabilidade:** Aplicar regras de negócio: criar billing, gerar parcelas, calcular juros, atualizar status.

**Conceitos para praticar:**
- **Business Logic Layer** — Toda regra de negócio fica aqui
- **`Event::dispatch('billing.create.before', $data)`** — Padrão Krayin de eventos antes/depois
- **Event Dispatching** — Permite que outros módulos reajam sem acoplamento
- **Transações** — `DB::transaction()` para garantir consistência ao criar billing + parcelas

---

## Passo 8 — Jobs (Fila Assíncrona)

**O que:** Criar o Job que roda na fila e consome o BillingProcessService.

**Arquivo:**
- `Jobs/ProcessBillingJob.php`

**Conceitos para praticar:**
- **`ShouldQueue`** — Interface que marca o job para rodar na fila (não síncrono)
- **`InteractsWithQueue`** — Trait para interagir com a fila (delete, release, etc.)
- **`Queueable`** — Trait que define a fila, prioridade, atraso
- **`SerializesModels`** — Serializa models automaticamente (usa IDs, re-busca ao deserializar)
- **`handle(BillingProcessService $service)`** — Método que executa o trabalho. Recebe dependências via injection.
- **`failed(Throwable $e)`** — Callback quando o job falha após todas as tentativas
- **`$tries`** — Quantas vezes tentar antes de ir para `failed_jobs`
- **`$backoff`** — Tempo de espera entre tentativas (exponential backoff)
- **`$queue`** — Qual fila usar (ex: `'billing'`, `'default'`)
- **`$priority`** — Prioridade do job (menor = mais prioritário)

### Configuração de Fila

**Conceitos para praticar:**
- **`config/queue.php`** — Configuração de drivers (redis, sqs, database, sync)
- **`QUEUE_CONNECTION`** no `.env` — Driver ativo (redis recomendado para produção)
- **`php artisan queue:work`** — Worker que consome a fila
- **`php artisan queue:listen`** — Worker que reinicia a cada job (dev)
- **`Bus::batch()`** — Processar múltiplos jobs em paralelo com tracking de progresso
- **`Bus::chain()`** — Jobs em sequência, um após o outro
- **`php artisan queue:failed`** — Lista jobs que falharam
- **`php artisan queue:retry {id}`** — Re-tentar um job específico

### Redis vs RabbitMQ

| | Redis Queue | RabbitMQ |
|---|-------------|----------|
| **Setup** | Simples (já usa Redis) | Requer serviço separado |
| **Features** | Básico (FIFO, delay, retry) | Avançado (routing, priorities, dead letter) |
| **Performance** | Milhares/s | Milhões de msgs/s |
| **Ideal para** | Dev / carga média | Produção / alta carga |

**Recomendação:** Comece com Redis Queue. Migre para RabbitMQ se precisar de features avançadas (dead lettering, routing por exchange, etc.).

---

## Passo 9 — Registrar no Concord

**O que:** Ativar o módulo adicionando o provider ao `config/concord.php`.

**Arquivo:**
- `config/concord.php`

**Conceitos para praticar:**
- **Config merging** — O Laravel combina configs de múltiplos providers
- **Concord module registration** — O array `modules` lista todos os providers que o Concord deve carregar

---

## Passo 10 — Controller + Rotas (Admin)

**O que:** Criar o controller e rotas no módulo Admin para upload do CSV e listagem de cobranças.

**Arquivos (no módulo Admin):**
- `packages/Webkul/Admin/src/Http/Controllers/BillingController.php`
- `packages/Webkul/Admin/src/Routes/billing.php`
- `packages/Webkul/Admin/src/DataGrids/Billing/BillingDataGrid.php`

**Conceitos para praticar:**
- **Controllers ficam no Admin** — Domain modules são puros; Admin é a camada de apresentação
- **`Route::resource()`** — Gera rotas CRUD automaticamente
- **`$request->validate()`** — Validação de upload: `mimes:xlsx,csv|max:10240`
- **DataGrid** — Listagem de cobranças com filtros, ações e mass actions
- **ACL** — `bouncer()->hasPermission('billing.view')` para controle de acesso

---

## Conceitos-Chave para Referência

| Conceito | Quando usar |
|----------|-------------|
| PHP 8.1 Enums | Representar status de forma type-safe |
| Concord ModelProxy | Relationships desacopladas entre módulos |
| Contract/Interface | Trocar implementação sem quebrar o sistema |
| Repository Pattern (Prettus) | Abstrair acesso a dados (similar ao Spring Data) |
| Service Layer | Separar importação de processamento |
| `ShouldQueue` + Jobs | Processamento assíncrono via fila |
| `Bus::batch()` / `Bus::chain()` | Processamento em paralelo ou sequencial |
| Eloquent Observers | Reagir a create/update sem poluir o model |
| Event Dispatching | Comunicação entre módulos sem acoplamento |
| Strategy Pattern | Trocar gateway de pagamento em runtime |
| `$casts` | Conversão automática de tipos no model |
| `decimal(18,2)` | Valores financeiros (nunca float) |

---

## Ordem de Execução Sugerida

1. Enums (mais simples, aquece) ✅
2. Migrations (define a estrutura do banco) ✅
3. Contracts (interfaces marker) ✅
4. Models + Proxies (entidades vivas) ✅
5. Providers (ativa o módulo) ✅
6. Repositories (acesso aos dados) ✅
7. Services (importação + processamento)
8. Jobs (fila assíncrona)
9. Concord config (liga o módulo)
10. Controller + Rotas + DataGrid (interface do usuário)

---

## Riscos

- **Valores financeiros** — Nunca usar `float`. Sempre `decimal` no banco.
- **Race conditions** — Pagamento duplicado. Considerar locks (`DB::lockForUpdate()`) ou transações.
- **FK para persons** — Usar `onDelete('restrict')` para proteger dados referenciados.
- **Queue driver** — Processamento assíncrono depende de queue configurada (Redis/RabbitMQ).
- **Memória no import** — CSV grande pode estourar memória. Usar `fgetcsv()` linha a linha, não `file_get_contents()`.
- **Jobs órfãos** — Worker cai no meio do processamento. Configurar `$tries` e `failed_jobs` table.
