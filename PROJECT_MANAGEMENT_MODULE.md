# Project Management Module - Modular Components Guide

## Overview
All project management components have been built modularly and can be reused throughout the system as individual modules.

## Modular Components

### Project Components (`resources/js/Components/projects/`)

#### 1. **ProjectCard** - Reusable Project Display Card
```jsx
import { ProjectCard } from '@/Components/projects';

<ProjectCard project={project} showActions={true} />
```
**Use Cases:**
- Dashboard widgets
- Project listings
- Recent projects sections
- Anywhere you need to display project information

#### 2. **TaskCard** - Reusable Task Display Card
```jsx
import { TaskCard } from '@/Components/projects';

<TaskCard task={task} onStatusChange={handleChange} />
```
**Use Cases:**
- Task lists
- Kanban boards
- Task dashboards
- Activity feeds

#### 3. **MilestoneCard** - Reusable Milestone Display Card
```jsx
import { MilestoneCard } from '@/Components/projects';

<MilestoneCard milestone={milestone} />
```
**Use Cases:**
- Milestone timelines
- Progress tracking
- Roadmap views

#### 4. **ProjectStatsCards** - Project Statistics Display
```jsx
import { ProjectStatsCards } from '@/Components/projects';

<ProjectStatsCards stats={stats} />
```
**Individual Stats Cards:**
- `TaskStatsCard` - Task completion stats
- `BudgetStatsCard` - Budget utilization stats
- `TimeStatsCard` - Time tracking stats

**Use Cases:**
- Dashboard widgets
- Summary views
- Analytics pages

#### 5. **ProjectQuickActions** - Quick Action Buttons
```jsx
import { ProjectQuickActions } from '@/Components/projects';

<ProjectQuickActions showReports={true} />
```
**Use Cases:**
- Dashboard quick actions
- Sidebar widgets
- Navigation panels

#### 6. **ProjectTabs** - Project Detail Page Tabs
```jsx
import { ProjectTabs } from '@/Components/projects';

<ProjectTabs projectId={projectId} activeTab="overview" />
```
**Use Cases:**
- Project detail pages
- Project management interfaces

#### 7. **ProjectManagementTabs** - Project Management Section Tabs
```jsx
import { ProjectManagementTabs } from '@/Components/projects';

<ProjectManagementTabs currentPath={currentPath} />
```
**Use Cases:**
- Project Management section navigation
- Section-level tabs

### Report Components (`resources/js/Components/reports/`)

#### 1. **ReportCard** - Individual Report Card
```jsx
import { ReportCard } from '@/Components/reports';

<ReportCard report={reportConfig} />
```

#### 2. **ReportCardsGrid** - Grid of Report Cards
```jsx
import { ReportCardsGrid } from '@/Components/reports';

<ReportCardsGrid reports={reportsArray} />
```
**Use Cases:**
- Report index pages
- Dashboard report sections
- Analytics hubs

#### 3. **ReportFilters** - Reusable Report Filter Component
```jsx
import { ReportFilters } from '@/Components/reports';

<ReportFilters
    filters={filters}
    onFilterChange={handleChange}
    filterConfig={[
        { key: 'dateFrom', label: 'Date From', type: 'date' },
        { key: 'projectId', label: 'Project', type: 'select', options: [...] },
    ]}
/>
```
**Use Cases:**
- Any report page
- Analytics pages
- Data filtering interfaces

## Project Management Section Structure

### Navigation
- **Overview** (`/projects/section`) - Main project management dashboard
- **Reports** (`/projects/reports`) - Project reports hub

### Reports Available
1. **Performance Report** - Project performance metrics
2. **Time Tracking Report** - Time analysis by project/user
3. **Budget Report** - Budget utilization and spending

## Usage Examples

### Using Project Stats in Dashboard
```jsx
import { ProjectStatsCards, TaskStatsCard } from '@/Components/projects';

// In your dashboard component
<ProjectStatsCards stats={projectStats} />
<TaskStatsCard taskStats={{ total: 10, done: 5 }} />
```

### Using Report Components in Custom Pages
```jsx
import { ReportCardsGrid, ReportFilters } from '@/Components/reports';

// Custom reports page
<ReportFilters filters={filters} filterConfig={filterConfig} />
<ReportCardsGrid reports={availableReports} />
```

### Using Project Cards in Lists
```jsx
import { ProjectCard } from '@/Components/projects';

{projects.map(project => (
    <ProjectCard key={project.id} project={project} />
))}
```

## All Components Export

### Projects
```jsx
import {
    ProjectCard,
    TaskCard,
    MilestoneCard,
    ProjectTabs,
    ProjectManagementTabs,
    ProjectStatsCards,
    TaskStatsCard,
    BudgetStatsCard,
    TimeStatsCard,
    ProjectQuickActions,
    ProjectSectionLayout,
} from '@/Components/projects';
```

### Reports
```jsx
import {
    ReportCard,
    ReportCardsGrid,
    ReportFilters,
} from '@/Components/reports';
```

## Benefits of Modular Design

1. **Reusability** - Use components anywhere in the system
2. **Consistency** - Same look and feel across the application
3. **Maintainability** - Update once, changes everywhere
4. **Flexibility** - Mix and match components as needed
5. **Performance** - Optimized, focused components

## Integration Points

All components can be integrated into:
- Main Dashboard
- Section pages
- Custom pages
- Widgets
- Sidebars
- Modals
- Any React component

