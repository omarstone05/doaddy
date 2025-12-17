# 🤖 ADDY AI - COMPLETE CODE REVIEW DOCUMENT

**Purpose**: Complete codebase documentation for external review  
**Date**: November 10, 2025  
**Version**: Phase 5 Complete

---

## 📋 TABLE OF CONTENTS

1. [Core Services](#core-services)
2. [Intelligence Agents](#intelligence-agents)
3. [Action System](#action-system)
4. [Conversational Layer](#conversational-layer)
5. [Cultural & Predictive Engines](#cultural--predictive-engines)
6. [Models](#models)
7. [Controllers](#controllers)
8. [Jobs & Commands](#jobs--commands)
9. [Middleware](#middleware)
10. [AI Service](#ai-service)

---

## CORE SERVICES

### 1. AddyCoreService

**File**: `app/Services/Addy/AddyCoreService.php`

**Purpose**: Central cognitive engine that orchestrates all Addy operations. This is the "brain" of Addy.

**What it should do**:
- Run a 5-step decision loop: Perception → Context Analysis → Decision → Insight Generation → State Update
- Coordinate all 4 intelligence agents (Money, Sales, People, Inventory)
- Generate cross-section insights that connect data across business areas
- Maintain Addy's current state (focus area, urgency, mood, priorities)
- Provide current thought summary for frontend display

**Key Methods**:
- `runDecisionLoop()`: Main cognitive cycle that processes all business data
- `perceive()`: Gathers data from all 4 agents
- `analyzeContext()`: Identifies issues, opportunities, and observations
- `makeDecision()`: Determines focus area, urgency, mood, and priorities
- `generateInsights()`: Creates actionable insights from agent data
- `generateCrossSectionInsights()`: Strategic insights connecting multiple business areas
- `getCurrentThought()`: Returns formatted thought summary for UI

**Flow**:
1. All agents perceive their domain data
2. Context analysis identifies issues/opportunities
3. Decision determines what Addy should focus on
4. Insights are generated from each agent + cross-section analysis
5. State is updated in database

---

### 2. AddyCommandParser

**File**: `app/Services/Addy/AddyCommandParser.php`

**Purpose**: Parses user messages to determine intent and extract parameters.

**What it should do**:
- Recognize 11+ intent types (greeting, cash query, budget query, action requests, etc.)
- Identify action requests (create, confirm, record, send, etc.)
- Extract parameters from messages (amount, flow_type, category, description)
- Enrich action parameters from chat history when missing

**Recognized Intents**:
- `action`: Create transaction, send invoice, generate report
- `greeting`: Hello, hi, hey, good morning/afternoon/evening
- `query_cash`: Cash position, balance, money queries
- `query_budget`: Budget status queries
- `query_expenses`: Expense queries
- `query_invoices`: Invoice queries (with type: overdue/pending/paid)
- `query_sales`: Sales performance queries
- `query_team`: Team/staff queries
- `query_payroll`: Payroll queries
- `query_inventory`: Stock/inventory queries
- `query_focus`: Focus/priority queries
- `query_insights`: Insight/alert queries
- `general`: Everything else

**Action Recognition**:
- Keywords: create, confirm, record, log, add, enter, send, generate, approve, schedule
- Patterns: "create transaction", "confirm expense", "send invoice reminders"
- Parameter extraction from message text and chat history

---

### 3. AddyResponseGenerator

**File**: `app/Services/Addy/AddyResponseGenerator.php`

**Purpose**: Generates conversational responses using OpenAI with cultural context.

**What it should do**:
- Route action requests to ActionExecutionService
- Fetch data context from agents for data queries
- Pass everything to OpenAI with comprehensive system message
- Apply cultural tone adaptation
- Generate quick actions based on intent

**Flow**:
1. **Action Requests** → Handled by code (ActionExecutionService)
2. **Data Queries** → Code fetches data, OpenAI formats conversationally
3. **General Conversation** → OpenAI with full cultural context

**Key Methods**:
- `generateResponse()`: Main entry point, routes to appropriate handler
- `enrichActionFromHistory()`: Extracts missing parameters from chat history
- `getDataContext()`: Fetches data from agents for queries
- `handleConversationalQuery()`: OpenAI-powered responses
- `buildConversationalSystemMessage()`: Comprehensive system prompt with:
  - Personality traits (warm, proactive, supportive)
  - Tone settings (professional, casual, motivational)
  - Cultural context (greetings, focus, suggestions)
  - Business context (focus area, insights, priorities)
  - Data context (when available)
  - Action capabilities (tells AI it CAN execute actions)

**System Message Components**:
- Personality: Warm, approachable, proactive, insightful
- Tone adaptation: Professional/casual/motivational
- Cultural context: Time-based greetings, day themes, proactive suggestions
- Business context: Current focus area, top insights, priorities
- Data context: Structured data from agents (when querying)
- Action capabilities: Explicitly tells AI it CAN execute actions

---

### 4. ActionExecutionService

**File**: `app/Services/Addy/ActionExecutionService.php`

**Purpose**: Prepares, executes, confirms, and rejects actions.

**What it should do**:
- Prepare actions with preview for user confirmation
- Execute confirmed actions
- Track action patterns for learning
- Suggest actions based on user behavior patterns

**Key Methods**:
- `prepareAction()`: Creates AddyAction with preview
- `executeAction()`: Executes confirmed action
- `confirmAction()`: Marks action as confirmed
- `rejectAction()`: Cancels action
- `getSuggestedActions()`: Returns AI-suggested actions based on patterns

**Action Flow**:
1. User requests action → `prepareAction()` creates preview
2. Preview shown to user → User confirms or cancels
3. If confirmed → `confirmAction()` → `executeAction()`
4. Pattern learned → `AddyActionPattern` updated
5. Future suggestions based on patterns

---

## INTELLIGENCE AGENTS

### 5. MoneyAgent

**File**: `app/Services/Addy/Agents/MoneyAgent.php`

**Purpose**: Perceives and analyzes financial data.

**What it should do**:
- Perceive: Cash position, account balances, budget health, expense trends, monthly burn
- Analyze: Budget overruns, spending spikes, cash flow issues
- Generate: Money-specific insights (budget warnings, cash alerts, expense trends)

**Perception Data**:
- `cash_position`: Total balance across all active accounts
- `budget_health`: Overrun/warning/healthy budgets
- `top_expenses`: Top 3 expense categories this month
- `monthly_burn`: Total expenses this month
- `trends`: Spending trend (increasing/decreasing/stable) with percentage change

**Generated Insights**:
- Budget overrun alerts (priority 0.9)
- Budget warnings (priority 0.6)
- Spending spike alerts (priority 0.8)
- Top expenses observation (priority 0.7)

---

### 6. SalesAgent

**File**: `app/Services/Addy/Agents/SalesAgent.php`

**Purpose**: Perceives and analyzes sales data.

**What it should do**:
- Perceive: Sales performance, customer stats, invoice health, quote conversion, payment trends
- Analyze: Sales trends, overdue invoices, customer growth
- Generate: Sales-specific insights (overdue invoices, sales decline/growth, customer opportunities)

**Perception Data**:
- `customer_stats`: Total, active, new this month
- `invoice_health`: Overdue/pending counts and amounts
- `sales_performance`: Current vs last month, trend, change percentage
- `quote_conversion`: Conversion rate, pending/rejected counts
- `payment_trends`: Average days to payment

**Generated Insights**:
- Overdue invoices alert (priority 0.85)
- Outstanding invoices observation (priority 0.5)
- Sales decline alert (priority 0.8)
- Sales growth achievement (priority 0.6)
- Low quote conversion suggestion (priority 0.65)
- New customer growth achievement (priority 0.5)
- Slow payment collection suggestion (priority 0.7)

---

### 7. PeopleAgent

**File**: `app/Services/Addy/Agents/PeopleAgent.php`

**Purpose**: Perceives and analyzes team/HR data.

**What it should do**:
- Perceive: Team stats, payroll health, leave patterns, attendance trends
- Analyze: Payroll due dates, leave conflicts, team capacity
- Generate: People-specific insights (payroll alerts, leave management, team expansion)

**Perception Data**:
- `team_stats`: Total, active, on leave, new this month
- `payroll_health`: This month total, last month total, next payroll date/amount, days until payroll
- `leave_patterns`: This month count, upcoming count, pending requests, total days
- `attendance_trends`: Average attendance rate, late arrivals, early departures

**Generated Insights**:
- Payroll due soon alert (priority 0.85)
- Pending leave requests suggestion (priority 0.6)
- High leave volume observation (priority 0.65)
- Payroll cost increase observation (priority 0.55)
- Team expansion achievement (priority 0.4)

---

### 8. InventoryAgent

**File**: `app/Services/Addy/Agents/InventoryAgent.php`

**Purpose**: Perceives and analyzes inventory/stock data.

**What it should do**:
- Perceive: Stock levels, low stock items, out of stock items, stock movements, inventory value
- Analyze: Out-of-stock risks, reorder needs, inventory efficiency
- Generate: Inventory-specific insights (stockout warnings, reorder suggestions)

**Perception Data**:
- `stock_levels`: Total products, healthy, low stock, out of stock counts
- `low_stock_items`: Array of items below reorder level
- `out_of_stock`: Array of items with zero stock
- `stock_movements`: Total movements, sales, purchases, adjustments
- `inventory_value`: Total value of all inventory

**Generated Insights**:
- Out of stock alert (priority 0.9)
- Low stock warning (priority 0.75)
- Inventory value observation (priority 0.4)
- High inventory activity observation (priority 0.5)

---

## ACTION SYSTEM

### 9. ActionRegistry

**File**: `app/Services/Addy/Actions/ActionRegistry.php`

**Purpose**: Central registry for all available actions.

**What it should do**:
- Register all action types with their handlers
- Provide action definitions (class, category, label, description)
- Retrieve actions by type or category

**Registered Actions**:
- `send_invoice_reminders`: Send payment reminder emails
- `create_transaction`: Record income/expense transaction
- `adjust_budget`: Modify budget allocation
- `create_invoice`: Generate new invoice
- `follow_up_quote`: Send follow-up for pending quotes
- `approve_leave`: Approve leave request
- `schedule_meeting`: Schedule team meeting
- `generate_report`: Generate business report
- `export_data`: Export data to CSV/Excel

---

### 10. BaseAction

**File**: `app/Services/Addy/Actions/BaseAction.php`

**Purpose**: Abstract base class for all actions.

**What it should do**:
- Define common interface for all actions
- Provide default implementations for optional methods
- Handle permissions and impact assessment

**Abstract Methods** (must be implemented):
- `validate()`: Validates parameters
- `preview()`: Returns action preview for confirmation
- `execute()`: Executes the action

**Optional Methods** (can be overridden):
- `canUndo()`: Whether action can be undone (default: false)
- `undo()`: Reverses the action
- `getImpact()`: Returns impact level (low/medium/high)
- `getRequiredPermissions()`: Returns required permissions
- `hasPermissions()`: Checks if user has permissions

---

### 11. CreateTransactionAction

**File**: `app/Services/Addy/Actions/CreateTransactionAction.php`

**Purpose**: Creates a money movement (income/expense) transaction.

**What it should do**:
- Validate: Requires `amount` and `flow_type` (income/expense)
- Preview: Shows transaction details, account, category, description
- Execute: Creates MoneyMovement record, updates account balance
- Undo: Can reverse transaction and restore account balance

**Parameters**:
- `amount`: Transaction amount (required)
- `flow_type`: 'income' or 'expense' (required)
- `account_id`: Account to use (optional, uses default if not provided)
- `category`: Transaction category (optional)
- `description`: Transaction description (optional)
- `date`: Transaction date (optional, defaults to now)

**Default Account Logic**:
- If `account_id` not provided, uses first active account for organization
- Shows warning in preview if no account specified
- Throws exception on execute if no account available

---

### 12. SendInvoiceRemindersAction

**File**: `app/Services/Addy/Actions/SendInvoiceRemindersAction.php`

**Purpose**: Sends payment reminder emails for overdue invoices.

**What it should do**:
- Preview: Lists all overdue invoices with customer, amount, days overdue
- Execute: Sends reminder emails (TODO: implement actual email sending)
- Impact: High if >10 invoices, medium if >3, low otherwise

**Email Preview**:
- Includes customer name, invoice number, amount, days overdue
- Friendly reminder tone
- Payment request

**TODO**: Implement actual email sending via Laravel notifications

---

## CONVERSATIONAL LAYER

### 13. AddyChatController

**File**: `app/Http/Controllers/AddyChatController.php`

**Purpose**: API endpoints for chat functionality.

**What it should do**:
- `sendMessage()`: Process user message, generate response, save to database
- `getHistory()`: Retrieve chat history (last 50 messages)
- `clearHistory()`: Delete all chat messages for user

**Flow**:
1. Save user message to database
2. Parse intent using AddyCommandParser
3. Get recent chat history (last 5 messages)
4. Generate response using AddyResponseGenerator
5. Link actions to chat messages
6. Save assistant response with metadata
7. Return response with quick actions and action previews

**Endpoints**:
- `POST /api/addy/chat`: Send message
- `GET /api/addy/chat/history`: Get chat history
- `DELETE /api/addy/chat/history`: Clear chat history

---

### 14. AddyActionController

**File**: `app/Http/Controllers/AddyActionController.php`

**Purpose**: API endpoints for action management.

**What it should do**:
- `confirm()`: Confirm and execute action
- `cancel()`: Cancel pending action
- `rate()`: Rate executed action (1-5 stars)
- `history()`: Get action history
- `suggestions()`: Get AI-suggested actions

**Authorization**:
- Manual checks: User must own action
- Status validation: Actions must be in correct status
- Uses `AuthorizesRequests` trait

**Endpoints**:
- `POST /api/addy/actions/{action}/confirm`: Confirm and execute
- `POST /api/addy/actions/{action}/cancel`: Cancel action
- `POST /api/addy/actions/{action}/rate`: Rate action
- `GET /api/addy/actions/history`: Get history
- `GET /api/addy/actions/suggestions`: Get suggestions

---

## CULTURAL & PREDICTIVE ENGINES

### 15. AddyCulturalEngine

**File**: `app/Services/Addy/AddyCulturalEngine.php`

**Purpose**: Personalizes communication based on user patterns and preferences.

**What it should do**:
- Provide contextual greetings based on time and day
- Adapt tone (professional, casual, motivational)
- Chunk tasks for ADHD mode
- Provide proactive suggestions based on time/patterns
- Respect quiet hours
- Adapt to user's peak hours

**Key Methods**:
- `getContextualGreeting()`: Time-aware greeting with day theme
- `adaptTone()`: Applies tone transformation to messages
- `chunkTasks()`: Breaks tasks for ADHD mode
- `getProactiveSuggestion()`: Time-based suggestions
- `shouldShowPredictions()`: Whether to show predictions
- `getRecommendedFocus()`: Day/time-based focus recommendations
- `getSettings()`: Returns cultural settings

**Cultural Context**:
- Time-based greetings (morning/afternoon/evening)
- Day themes (Monday: Deep Work, Tuesday: Build, etc.)
- Peak hours adaptation
- Quiet hours respect
- ADHD mode support

---

### 16. AddyPredictiveEngine

**File**: `app/Services/Addy/AddyPredictiveEngine.php`

**Purpose**: Generates predictions for business metrics.

**What it should do**:
- Predict cash flow for 30, 60, 90 days
- Predict budget burn (when budgets will hit limits)
- Predict sales revenue for next month
- Predict inventory reorder needs

**Predictions**:
- **Cash Flow**: Based on current cash, average monthly expenses/revenue, upcoming events
- **Budget Burn**: Based on daily burn rate, remaining budget
- **Sales Revenue**: Based on last 3 months trend, seasonality
- **Inventory Needs**: Placeholder (to be enhanced)

**Confidence Calculation**:
- Based on data consistency, transaction volume
- Cash flow: 0.75 default
- Budget burn: 0.75
- Sales revenue: 0.7

**Adjustments**:
- Upcoming payroll reduces predicted cash
- Expected invoice payments increase predicted cash (70% collection rate)
- Seasonal factors (placeholder)

---

## MODELS

### 17. AddyState

**File**: `app/Models/AddyState.php`

**Purpose**: Stores Addy's cognitive state.

**Fields**:
- `organization_id`: FK to organizations
- `focus_area`: Current business focus (string)
- `urgency`: Urgency level (0.0-1.0)
- `context`: Current situation description
- `mood`: Addy's mood (neutral/concerned/optimistic)
- `perception_data`: JSON of all agent perceptions
- `priorities`: JSON array of priority items
- `last_thought_cycle`: Timestamp of last decision loop

**Methods**:
- `current()`: Get current state for organization
- `needsThoughtCycle()`: Check if thought cycle needed (24 hours)

---

### 18. AddyInsight

**File**: `app/Models/AddyInsight.php`

**Purpose**: Stores generated insights.

**Fields**:
- `organization_id`: FK to organizations
- `addy_state_id`: FK to addy_states
- `type`: alert/suggestion/observation/achievement
- `category`: money/sales/people/inventory/cross-section
- `title`: Insight title
- `description`: Detailed description
- `priority`: Priority level (0.0-1.0)
- `is_actionable`: Boolean
- `suggested_actions`: JSON array
- `action_url`: URL to take action
- `status`: active/dismissed/completed
- `dismissed_at`: Timestamp
- `completed_at`: Timestamp
- `expires_at`: Timestamp

**Methods**:
- `active()`: Get active insights for organization
- `dismiss()`: Mark as dismissed
- `complete()`: Mark as completed

---

### 19. AddyChatMessage

**File**: `app/Models/AddyChatMessage.php`

**Purpose**: Stores chat messages.

**Fields**:
- `organization_id`: FK to organizations
- `user_id`: FK to users
- `role`: user/assistant
- `content`: Message content
- `metadata`: JSON (intent, quick_actions, action)

**Methods**:
- `getRecentHistory()`: Get recent chat history for context (last N messages)

---

### 20. AddyAction

**File**: `app/Models/AddyAction.php`

**Purpose**: Stores action records.

**Fields**:
- `organization_id`: FK to organizations
- `user_id`: FK to users
- `chat_message_id`: FK to addy_chat_messages (nullable)
- `action_type`: create_transaction, send_invoice_reminders, etc.
- `category`: money/sales/people/reports
- `status`: pending/confirmed/executed/failed/cancelled
- `parameters`: JSON of action parameters
- `preview_data`: JSON of action preview
- `result`: JSON of execution result
- `executed_at`: Timestamp
- `failed_at`: Timestamp
- `error_message`: Error details
- `user_rating`: 1-5 star rating
- `user_feedback`: Text feedback

**Methods**:
- `confirm()`: Mark as confirmed
- `markExecuted()`: Mark as executed with result
- `fail()`: Mark as failed with error
- `cancel()`: Mark as cancelled

---

### 21. AddyActionPattern

**File**: `app/Models/AddyActionPattern.php`

**Purpose**: Learns from user behavior to suggest actions.

**Fields**:
- `organization_id`: FK to organizations
- `user_id`: FK to users
- `action_type`: Action type
- `times_suggested`: Times suggested
- `times_confirmed`: Times confirmed
- `times_rejected`: Times rejected
- `times_successful`: Times successfully executed
- `avg_rating`: Average user rating
- `last_suggested_at`: Timestamp
- `last_confirmed_at`: Timestamp
- `successful_contexts`: JSON array
- `failed_contexts`: JSON array

**Methods**:
- `getOrCreate()`: Get or create pattern
- `recordSuggestion()`: Increment suggestion count
- `recordConfirmation()`: Record confirmation with context
- `recordRejection()`: Record rejection with context
- `recordSuccess()`: Record successful execution with rating
- `getConfidence()`: Calculate confidence score (0-1)
- `shouldSuggest()`: Whether to suggest this action (confidence >= 0.6)

**Confidence Calculation**:
- Based on confirmation rate (60%) and success rate (40%)
- Minimum 3 data points required
- Returns 0.5 if insufficient data

---

### 22. AddyPrediction

**File**: `app/Models/AddyPrediction.php`

**Purpose**: Stores predictions.

**Fields**:
- `organization_id`: FK to organizations
- `type`: cash_flow/budget_burn/sales_revenue/inventory_needs
- `category`: money/sales/people/inventory
- `prediction_date`: Date prediction was made
- `target_date`: Date being predicted
- `predicted_value`: Numeric prediction
- `confidence`: Confidence level (0.0-1.0)
- `factors`: JSON of prediction factors
- `metadata`: JSON of additional metadata
- `actual_value`: Actual value (after target date)
- `accuracy`: Calculated accuracy (0.0-1.0)

**Methods**:
- `getLatest()`: Get latest prediction of a type
- `calculateAccuracy()`: Calculate accuracy after actual value is known

---

### 23. AddyCulturalSetting

**File**: `app/Models/AddyCulturalSetting.php`

**Purpose**: Organization-level cultural preferences.

**Fields**:
- `organization_id`: FK to organizations
- `tone`: professional/casual/motivational
- `timezone`: Timezone string
- `enable_predictions`: Boolean
- `enable_proactive_suggestions`: Boolean
- `max_daily_suggestions`: Integer
- `quiet_hours_start`: Time
- `quiet_hours_end`: Time
- `weekly_themes`: JSON
- `blocked_times`: JSON

**Methods**:
- `getOrCreate()`: Get or create settings with defaults
- `isInQuietHours()`: Check if current time is in quiet hours

---

### 24. AddyUserPattern

**File**: `app/Models/AddyUserPattern.php`

**Purpose**: User-specific behavior patterns.

**Fields**:
- `organization_id`: FK to organizations
- `user_id`: FK to users
- `weekly_rhythm`: JSON (day preferences)
- `peak_hours`: JSON array of hours
- `section_preferences`: JSON
- `avg_response_time`: Integer (minutes)
- `action_patterns`: JSON
- `dismissed_insight_types`: JSON
- `work_style`: focused/balanced/creative
- `adhd_mode`: Boolean
- `preferred_task_chunk_size`: Integer

**Methods**:
- `getOrCreate()`: Get or create pattern with defaults
- `getTodayTheme()`: Get today's theme from weekly rhythm
- `isInPeakHours()`: Check if current hour is peak hour
- `recordSectionVisit()`: Record section usage
- `recordInsightAction()`: Record insight interaction

**Default Weekly Rhythm**:
- Monday: Deep Work / planning
- Tuesday: Build / execution
- Wednesday: Collaborate / meetings
- Thursday: Review / analysis
- Friday: Creative / innovation
- Saturday: Rest / personal
- Sunday: Reflect / planning

---

## JOBS & COMMANDS

### 25. RunAddyDecisionLoop

**File**: `app/Jobs/RunAddyDecisionLoop.php`

**Purpose**: Scheduled job to run Addy's decision loop.

**What it should do**:
- Run `AddyCoreService->runDecisionLoop()` for an organization
- Log success/failure
- Queue for all organizations or specific one

**Schedule**: Daily (via `routes/console.php`)

**Command**: `php artisan addy:think {--org=}`

---

### 26. GenerateAddyPredictions

**File**: `app/Jobs/GenerateAddyPredictions.php`

**Purpose**: Scheduled job to generate predictions.

**What it should do**:
- Run `AddyPredictiveEngine->generatePredictions()` for an organization
- Log success/failure
- Queue for all organizations or specific one

**Schedule**: Daily at 7 AM (via `routes/console.php`)

**Command**: `php artisan addy:predict {--org=}`

---

### 27. RunAddyThoughtCycle

**File**: `app/Console/Commands/RunAddyThoughtCycle.php`

**Purpose**: Artisan command to manually trigger decision loop.

**What it should do**:
- Accept optional `--org` parameter
- Queue `RunAddyDecisionLoop` job for organization(s)
- Provide feedback on queued jobs

**Usage**: `php artisan addy:think [--org=ID]`

---

### 28. GeneratePredictions

**File**: `app/Console/Commands/GeneratePredictions.php`

**Purpose**: Artisan command to manually trigger prediction generation.

**What it should do**:
- Accept optional `--org` parameter
- Queue `GenerateAddyPredictions` job for organization(s)
- Provide feedback on queued jobs

**Usage**: `php artisan addy:predict [--org=ID]`

---

## MIDDLEWARE

### 29. ShareAddyData

**File**: `app/Http/Middleware/ShareAddyData.php`

**Purpose**: Shares Addy's state and insights with frontend via Inertia.

**What it should do**:
- Get current Addy state for authenticated user's organization
- Share via Inertia for all pages
- Provide default state if error occurs
- Don't share if user not authenticated

**Shared Data**:
- `addy.state`: Current AddyState
- `addy.thought`: Formatted thought summary
- `addy.insights_count`: Count of active insights

**Registered**: In `bootstrap/app.php` web middleware group

---

## AI SERVICE

### 30. AIService

**File**: `app/Services/AI/AIService.php`

**Purpose**: Unified interface for OpenAI and Anthropic APIs.

**What it should do**:
- Support both OpenAI and Anthropic providers
- Handle API key management (from platform_settings)
- Convert message formats between providers
- Handle errors gracefully

**Methods**:
- `chat()`: Send chat messages, get response
- `ask()`: Quick single-message query
- `chatOpenAI()`: OpenAI-specific implementation
- `chatAnthropic()`: Anthropic-specific implementation

**Configuration**:
- Stored in `platform_settings` table (encrypted)
- Settings: `ai_provider`, `openai_api_key`, `openai_model`, `anthropic_api_key`, `anthropic_model`

**Message Format**:
- OpenAI: Standard format with system/user/assistant roles
- Anthropic: Converts system message to separate field, maintains message array

---

## CROSS-SECTION INSIGHTS

**Location**: `AddyCoreService->generateCrossSectionInsights()`

**Purpose**: Generate insights that connect data across multiple business areas.

**Cross-Insights**:

1. **Low Inventory + High Sales = Stockout Risk**
   - Detects when inventory is low while sales are increasing
   - Priority: 0.88
   - Suggests: Prioritize restocking, increase reorder quantities

2. **Overdue Invoices + Upcoming Payroll = Cash Flow Squeeze**
   - Detects cash flow pressure from overdue invoices and upcoming payroll
   - Priority: 0.92
   - Suggests: Urgently follow up on invoices, review cash reserves

3. **Sales Decline + High Expenses = Profit Margin Squeeze**
   - Detects declining sales with rising expenses
   - Priority: 0.9
   - Suggests: Review expenses, implement sales recovery strategies

4. **High Leave Volume + Sales Goals = Capacity Planning**
   - Detects capacity constraints from leave and sales growth
   - Priority: 0.7
   - Suggests: Redistribute workload, consider temporary staff

5. **Budget Overrun in Inventory + Out of Stock = Poor Planning**
   - Detects inventory allocation inefficiency
   - Priority: 0.75
   - Suggests: Review purchasing strategy, implement demand forecasting

---

## DATA FLOW SUMMARY

### Chat Flow
1. User sends message → `AddyChatController@sendMessage`
2. Message saved to `addy_chat_messages`
3. `AddyCommandParser` parses intent
4. If action → `ActionExecutionService` prepares action
5. If query → `AddyResponseGenerator` gets data context
6. `AddyResponseGenerator` calls OpenAI with system message
7. OpenAI responds conversationally
8. Response saved to `addy_chat_messages`
9. Returned to frontend with quick actions

### Decision Loop Flow
1. Scheduled job triggers `RunAddyDecisionLoop`
2. `AddyCoreService@runDecisionLoop` executes:
   - **Perception**: All 4 agents gather data
   - **Context Analysis**: Identifies issues/opportunities
   - **Decision**: Determines focus, urgency, mood
   - **Insight Generation**: Creates insights from agents + cross-section
   - **State Update**: Updates `addy_states` table
3. Insights saved to `addy_insights` table
4. State shared via `ShareAddyData` middleware

### Action Execution Flow
1. User requests action in chat
2. `AddyCommandParser` recognizes action intent
3. `AddyResponseGenerator` calls `ActionExecutionService@prepareAction`
4. Action created in `addy_actions` (status: pending)
5. Preview returned to user via chat
6. `ActionConfirmation` component displays preview
7. User confirms → `AddyActionController@confirm`
8. `ActionExecutionService@executeAction` runs action
9. Action status updated to "executed"
10. Result returned to user
11. User can rate action (1-5 stars)
12. Pattern learned in `addy_action_patterns`

---

## KEY DESIGN DECISIONS

1. **Code + AI Hybrid**: Code handles data fetching and actions, AI handles conversational formatting
2. **Multi-Agent Architecture**: Separate agents for each business domain
3. **Cross-Section Insights**: Strategic insights connecting multiple areas
4. **Cultural Intelligence**: Personalized communication based on user patterns
5. **Action Learning**: System learns from user behavior to suggest actions
6. **Predictive Analytics**: Forecasting for cash flow, budget, sales, inventory
7. **Conversational Memory**: Chat history provides context for better responses
8. **Parameter Enrichment**: Missing action parameters extracted from chat history

---

## KNOWN ISSUES & TODOS

1. **SendInvoiceRemindersAction**: Email sending not implemented (TODO)
2. **Inventory Predictions**: Placeholder implementation (to be enhanced)
3. **Action Undo**: Only `CreateTransactionAction` implements undo
4. **Permissions**: Permission checking is placeholder (always returns true)
5. **Email Notifications**: Invoice reminders need actual email implementation
6. **Seasonality**: Sales predictions don't account for seasonality yet
7. **Confidence Calculation**: Simplified, could be more sophisticated

---

## TESTING RECOMMENDATIONS

1. **Unit Tests**: Test each agent's perception and analysis
2. **Integration Tests**: Test decision loop end-to-end
3. **Action Tests**: Test action preparation, execution, undo
4. **Chat Tests**: Test intent parsing, response generation
5. **Pattern Learning**: Test action pattern confidence calculation
6. **Cross-Section Insights**: Test all 5 cross-insight scenarios
7. **Cultural Engine**: Test tone adaptation, quiet hours, peak hours
8. **Predictive Engine**: Test prediction accuracy over time

---

**Document Generated**: November 10, 2025  
**Total Files Documented**: 30  
**Status**: Complete for External Review

