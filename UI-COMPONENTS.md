# UI Component Library

A comprehensive, reusable UI component library for React applications. Built with Tailwind CSS, Framer Motion, and Lucide icons.

## Table of Contents

- [Installation](#installation)
- [Design Tokens](#design-tokens)
- [Core Components](#core-components)
  - [Card](#card)
  - [StatValue](#statvalue)
  - [Badge](#badge)
  - [Avatar](#avatar)
  - [Button](#button)
  - [ProgressBar](#progressbar)
- [Form Components](#form-components)
  - [Input](#input)
  - [Textarea](#textarea)
  - [Select](#select)
  - [DateInput](#dateinput)
- [Modal Components](#modal-components)
  - [Modal](#modal)
  - [ConfirmModal](#confirmmodal)
  - [SlidePanel](#slidepanel)
- [Decorative Components](#decorative-components)

---

## Installation

### Dependencies

```bash
npm install framer-motion lucide-react
```

### Required CSS Variables (Tailwind)

Add these to your `app.css` or Tailwind config:

```css
@theme {
    /* Primary Brand Color */
    --color-projjo-50: #fef2f2;
    --color-projjo-100: #fee2e2;
    --color-projjo-200: #fecaca;
    --color-projjo-300: #fca5a5;
    --color-projjo-400: #f87171;
    --color-projjo-500: #DC143C;  /* Classic Crimson */
    --color-projjo-600: #c01236;
    --color-projjo-700: #a31030;
    --color-projjo-800: #860d28;
    --color-projjo-900: #6b0a20;

    /* Accent Colors */
    --color-charcoal-50: #f7f7f6;
    --color-charcoal-100: #e5e5e3;
    --color-charcoal-200: #cbcbc7;
    --color-charcoal-300: #a9a9a3;
    --color-charcoal-400: #87877f;
    --color-charcoal-500: #6c6c64;
    --color-charcoal-600: #565650;
    --color-charcoal-700: #474742;
    --color-charcoal-800: #3c3c38;
    --color-charcoal-900: #353531;  /* Charcoal Brown */

    --color-saffron-50: #fffbeb;
    --color-saffron-100: #fef3c7;
    --color-saffron-200: #fde68a;
    --color-saffron-300: #fcd34d;
    --color-saffron-400: #fbbf24;
    --color-saffron-500: #FF9505;  /* Deep Saffron */
    --color-saffron-600: #d97706;
    --color-saffron-700: #b45309;
    --color-saffron-800: #92400e;
    --color-saffron-900: #78350f;
}
```

---

## Design Tokens

### Color Palette

| Token | Hex | Usage |
|-------|-----|-------|
| `projjo-500` | `#DC143C` | Primary brand, buttons, links |
| `charcoal-900` | `#353531` | Text, dark accents |
| `saffron-500` | `#FF9505` | Secondary accent, highlights |
| `emerald-500` | `#10b981` | Success, positive states |
| `stone-*` | Various | Backgrounds, borders |

### Spacing & Sizing

| Token | Value | Usage |
|-------|-------|-------|
| `rounded-xl` | `0.75rem` | Buttons, inputs |
| `rounded-2xl` | `1rem` | Cards, modals |
| `shadow-sm` | Small | Default card shadow |
| `shadow-lg` | Large | Hover states |

---

## Core Components

### Card

Base card component with multiple style variants.

```jsx
import { Card, CardHeader } from './UI';

// Glass card (default)
<Card>
    <CardHeader label="Title" />
    Content here
</Card>

// Gradient positive (green)
<Card variant="gradient-positive">
    <CardHeader label="Revenue" badge={<Badge variant="positive">+12%</Badge>} />
    Content
</Card>

// Gradient negative (red/crimson)
<Card variant="gradient-negative">
    <CardHeader label="Overdue" />
    Content
</Card>

// Feature card (solid brand color)
<Card variant="feature" padding="lg">
    Featured content with white text
</Card>
```

#### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `variant` | `'glass' \| 'gradient-positive' \| 'gradient-negative' \| 'solid' \| 'feature'` | `'glass'` | Card style |
| `hover` | `boolean` | `true` | Enable hover shadow |
| `padding` | `'none' \| 'sm' \| 'md' \| 'lg'` | `'md'` | Internal padding |
| `onClick` | `function` | - | Click handler |

#### CardHeader Props

| Prop | Type | Description |
|------|------|-------------|
| `label` | `string` | Header label (uppercase) |
| `onAction` | `function` | Shows + button, triggers on click |
| `badge` | `ReactNode` | Custom badge element |
| `action` | `ReactNode` | Custom action element |

---

### StatValue

Large, prominent number display for statistics.

```jsx
import { StatValue, StatSubtitle } from './UI';

// Basic
<StatValue value={42} />

// With prefix and suffix
<StatValue value={1250} prefix="$" suffix="k" size="xl" />

// With subtitle
<StatValue value={12} size="lg" />
<StatSubtitle text="5 active" accentType="positive" />
```

#### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `value` | `string \| number` | - | Display value |
| `size` | `'sm' \| 'md' \| 'lg' \| 'xl'` | `'lg'` | Text size |
| `prefix` | `string` | `''` | Prefix (e.g., $, K) |
| `suffix` | `string` | `''` | Suffix (e.g., %, k) |

#### StatSubtitle Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `text` | `string` | - | Subtitle text (first word accented) |
| `accentType` | `'positive' \| 'negative' \| 'warning' \| 'neutral'` | `'positive'` | Accent color |

---

### Badge

Small status indicators and tags.

```jsx
import { Badge, ChangeBadge, StatusDot } from './UI';

// Basic badges
<Badge variant="positive">Active</Badge>
<Badge variant="negative">Overdue</Badge>
<Badge variant="warning">Pending</Badge>
<Badge variant="neutral">Draft</Badge>

// Change indicator
<ChangeBadge value="+12%" type="positive" />
<ChangeBadge value="-5%" type="negative" />

// Status dot
<StatusDot status="active" />
<StatusDot status="warning" />
<StatusDot status="critical" />
```

#### Badge Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `variant` | `'positive' \| 'negative' \| 'warning' \| 'neutral' \| 'feature'` | `'neutral'` | Color variant |
| `size` | `'xs' \| 'sm' \| 'md'` | `'sm'` | Badge size |

#### StatusDot Statuses

`active`, `success`, `paused`, `warning`, `completed`, `info`, `archived`, `critical`, `error`

---

### Avatar

User avatar display with fallback to initials.

```jsx
import { Avatar, AvatarStack } from './UI';

// Single avatar
<Avatar name="John Doe" size="md" />
<Avatar name="Jane" src="/avatar.jpg" size="lg" />

// Avatar stack (team members)
<AvatarStack 
    members={[
        { id: 1, name: 'Alice' },
        { id: 2, name: 'Bob' },
        { id: 3, name: 'Charlie' },
        { id: 4, name: 'Diana' },
        { id: 5, name: 'Eve' },
    ]}
    max={4}
/>
```

#### Avatar Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `name` | `string` | - | User name (for initial) |
| `src` | `string` | - | Image URL |
| `size` | `'sm' \| 'md' \| 'lg' \| 'xl'` | `'md'` | Avatar size |
| `gradient` | `string` | `'from-projjo-400 to-projjo-600'` | Fallback gradient |

#### AvatarStack Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `members` | `array` | `[]` | Array of {id, name, avatar?} |
| `max` | `number` | `4` | Max visible avatars |
| `size` | `'sm' \| 'md' \| 'lg'` | `'md'` | Avatar size |

---

### Button

Action buttons with multiple variants.

```jsx
import { Button, IconButton } from './UI';
import { Plus, Settings } from 'lucide-react';

// Button variants
<Button variant="primary">Save</Button>
<Button variant="secondary">Cancel</Button>
<Button variant="ghost">Learn More</Button>
<Button variant="danger">Delete</Button>
<Button variant="feature">Generate with AI</Button>

// With icon
<Button variant="primary" icon={Plus}>Add Project</Button>
<Button variant="secondary" icon={Settings} iconPosition="right">Settings</Button>

// Loading state
<Button variant="primary" loading>Saving...</Button>

// Icon button
<IconButton icon={Settings} variant="ghost" />
```

#### Button Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `variant` | `'primary' \| 'secondary' \| 'ghost' \| 'danger' \| 'feature'` | `'primary'` | Button style |
| `size` | `'sm' \| 'md' \| 'lg'` | `'md'` | Button size |
| `icon` | `LucideIcon` | - | Icon component |
| `iconPosition` | `'left' \| 'right'` | `'left'` | Icon placement |
| `loading` | `boolean` | `false` | Show loading spinner |
| `disabled` | `boolean` | `false` | Disable button |

---

### ProgressBar

Horizontal progress indicator.

```jsx
import { ProgressBar } from './UI';

// Basic
<ProgressBar value={65} />

// With label
<ProgressBar value={65} showLabel />

// Animated
<ProgressBar value={65} animated />

// Variants
<ProgressBar value={80} variant="positive" />
<ProgressBar value={30} variant="warning" />
```

#### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `value` | `number` | `0` | Current value |
| `max` | `number` | `100` | Maximum value |
| `variant` | `'primary' \| 'positive' \| 'warning' \| 'feature'` | `'primary'` | Color |
| `size` | `'sm' \| 'md' \| 'lg'` | `'md'` | Bar height |
| `showLabel` | `boolean` | `false` | Show percentage label |
| `animated` | `boolean` | `false` | Animate on mount |

---

## Form Components

### Input

Standard text input with label and error handling.

```jsx
import { Input } from './UI/Form';

<Input
    label="Project Name"
    required
    placeholder="e.g., Website Redesign"
    value={name}
    onChange={(e) => setName(e.target.value)}
    error={errors.name}
/>
```

#### Props

| Prop | Type | Description |
|------|------|-------------|
| `label` | `string` | Input label |
| `required` | `boolean` | Show required indicator |
| `error` | `string` | Error message |
| `...props` | - | All standard input props |

---

### Textarea

Multi-line text input.

```jsx
import { Textarea } from './UI/Form';

<Textarea
    label="Description"
    rows={4}
    placeholder="Describe your project..."
    value={description}
    onChange={(e) => setDescription(e.target.value)}
/>
```

---

### Select

Dropdown select input.

```jsx
import { Select } from './UI/Form';

<Select
    label="Status"
    options={[
        { value: 'active', label: 'Active' },
        { value: 'paused', label: 'Paused' },
        { value: 'completed', label: 'Completed' },
    ]}
    value={status}
    onChange={(e) => setStatus(e.target.value)}
/>
```

---

### DateInput

Date picker input.

```jsx
import { DateInput } from './UI/Form';

<DateInput
    label="Start Date"
    value={startDate}
    onChange={(e) => setStartDate(e.target.value)}
/>
```

---

### FormGroup & FormActions

Layout helpers for forms.

```jsx
import { FormGroup, FormActions } from './UI/Form';
import { Button } from './UI';

<FormGroup columns={2}>
    <Input label="First Name" />
    <Input label="Last Name" />
</FormGroup>

<FormActions align="right">
    <Button variant="ghost">Cancel</Button>
    <Button variant="primary">Save</Button>
</FormActions>
```

#### FormGroup Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `columns` | `1 \| 2 \| 3 \| 4` | `1` | Grid columns |

#### FormActions Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `align` | `'left' \| 'center' \| 'right' \| 'between'` | `'right'` | Button alignment |

---

## Modal Components

### Modal

Base modal with backdrop and animations.

```jsx
import { Modal, ModalHeader, ModalBody, ModalFooter } from './UI/Modal';
import { Button } from './UI';

<Modal isOpen={isOpen} onClose={onClose} size="md">
    <ModalHeader 
        title="Create Project" 
        subtitle="Start a new project"
        onClose={onClose}
    />
    <ModalBody>
        {/* Form content */}
    </ModalBody>
    <ModalFooter>
        <Button variant="ghost" onClick={onClose}>Cancel</Button>
        <Button variant="primary" onClick={handleSubmit}>Create</Button>
    </ModalFooter>
</Modal>
```

#### Modal Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `isOpen` | `boolean` | - | Show/hide modal |
| `onClose` | `function` | - | Close callback |
| `size` | `'sm' \| 'md' \| 'lg' \| 'xl' \| 'full'` | `'md'` | Modal width |
| `closeOnBackdrop` | `boolean` | `true` | Close on backdrop click |
| `closeOnEscape` | `boolean` | `true` | Close on Escape key |

---

### ConfirmModal

Pre-built confirmation dialog.

```jsx
import { ConfirmModal } from './UI/Modal';

<ConfirmModal
    isOpen={showConfirm}
    onClose={() => setShowConfirm(false)}
    onConfirm={handleDelete}
    title="Delete Project"
    message="Are you sure? This action cannot be undone."
    confirmLabel="Delete"
    variant="danger"
    loading={deleting}
/>
```

#### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `title` | `string` | `'Confirm Action'` | Dialog title |
| `message` | `string` | `'Are you sure?'` | Confirmation message |
| `confirmLabel` | `string` | `'Confirm'` | Confirm button text |
| `cancelLabel` | `string` | `'Cancel'` | Cancel button text |
| `variant` | `'primary' \| 'danger'` | `'primary'` | Confirm button style |
| `loading` | `boolean` | `false` | Show loading state |

---

### SlidePanel

Side panel that slides in from the right.

```jsx
import { SlidePanel, ModalHeader, ModalBody } from './UI/Modal';

<SlidePanel isOpen={isOpen} onClose={onClose} width="lg">
    <ModalHeader title="Task Details" onClose={onClose} />
    <ModalBody>
        {/* Panel content */}
    </ModalBody>
</SlidePanel>
```

#### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `isOpen` | `boolean` | - | Show/hide panel |
| `onClose` | `function` | - | Close callback |
| `width` | `'sm' \| 'md' \| 'lg' \| 'xl'` | `'md'` | Panel width |

---

## Decorative Components

### WaveDecoration

Animated wave SVG for gradient cards.

```jsx
import { WaveDecoration } from './UI';

<Card variant="gradient-positive">
    <WaveDecoration variant="positive" />
    {/* Content */}
</Card>
```

### DotPattern

Subtle dot pattern overlay.

```jsx
import { DotPattern } from './UI';

<div className="relative">
    <DotPattern id="unique-id" opacity={0.1} />
    {/* Content */}
</div>
```

### EmptyState

Placeholder for empty content areas.

```jsx
import { EmptyState } from './UI';
import { Folder } from 'lucide-react';

<EmptyState
    icon={Folder}
    title="No projects yet"
    description="Create your first project to get started"
    action="Create Project"
    onAction={() => setShowModal(true)}
/>
```

---

## Complete Example

```jsx
import { useState } from 'react';
import { Card, CardHeader, StatValue, StatSubtitle, Button, Badge, AvatarStack, ProgressBar, WaveDecoration } from './UI';
import { Modal, ModalHeader, ModalBody, ModalFooter } from './UI/Modal';
import { Input, Textarea, FormGroup, FormActions } from './UI/Form';

export default function Dashboard() {
    const [showModal, setShowModal] = useState(false);

    return (
        <div className="grid grid-cols-4 gap-4">
            {/* Stat Card */}
            <Card variant="gradient-positive">
                <WaveDecoration variant="positive" />
                <CardHeader 
                    label="Projects" 
                    onAction={() => setShowModal(true)} 
                />
                <StatValue value={12} size="xl" />
                <StatSubtitle text="5 active" accentType="positive" />
            </Card>

            {/* Team Card */}
            <Card>
                <CardHeader label="Team" />
                <StatValue value={8} />
                <AvatarStack 
                    members={[
                        { id: 1, name: 'Alice' },
                        { id: 2, name: 'Bob' },
                    ]} 
                />
            </Card>

            {/* Create Modal */}
            <Modal isOpen={showModal} onClose={() => setShowModal(false)}>
                <ModalHeader title="New Project" onClose={() => setShowModal(false)} />
                <ModalBody>
                    <Input label="Name" required className="mb-4" />
                    <Textarea label="Description" className="mb-4" />
                    <FormGroup columns={2}>
                        <Input label="Start Date" type="date" />
                        <Input label="End Date" type="date" />
                    </FormGroup>
                </ModalBody>
                <ModalFooter>
                    <Button variant="ghost" onClick={() => setShowModal(false)}>Cancel</Button>
                    <Button variant="primary">Create</Button>
                </ModalFooter>
            </Modal>
        </div>
    );
}
```

---

## Customization

### Theming

To customize for a different brand (e.g., Addy with teal):

```css
@theme {
    /* Replace projjo with your brand */
    --color-projjo-500: #14b8a6;  /* Teal */
    --color-projjo-600: #0d9488;
    /* ... etc */
}
```

### Adding New Variants

Extend the variant objects in each component:

```jsx
// In Card component
const variants = {
    glass: 'bg-white/80 ...',
    'gradient-positive': '...',
    // Add your custom variant
    'brand-special': 'bg-gradient-to-br from-purple-500 to-pink-500',
};
```

---

## License

MIT - Use freely in your projects.



