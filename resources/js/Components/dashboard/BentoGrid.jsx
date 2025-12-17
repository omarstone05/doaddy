import React from 'react';
import { 
  DndContext, 
  closestCenter, 
  PointerSensor, 
  useSensor, 
  useSensors 
} from '@dnd-kit/core';
import { SortableContext, rectSortingStrategy } from '@dnd-kit/sortable';
import { DraggableResizableCard } from './DraggableResizableCard';
import { GridEngine, GRID_CONFIG } from '@/lib/gridEngine';

export function BentoGrid({ 
  children, 
  items = [], 
  onReorder, 
  onResize, 
  onRemove,
  nonRemovableIds = [],
  showGrid = false // Debug mode to show grid lines
}) {
  const sensors = useSensors(
    useSensor(PointerSensor, {
      activationConstraint: {
        distance: 8,
      },
    })
  );

  const engine = new GridEngine(items);
  const rows = engine.calculateRows();

  const handleDragEnd = (event) => {
    const { active, over } = event;
    if (!over || active.id === over.id) return;

    const activeIndex = items.findIndex(item => `card-${item.id}` === active.id);
    const overIndex = items.findIndex(item => `card-${item.id}` === over.id);

    if (activeIndex === -1 || overIndex === -1) return;

    const activeItem = items[activeIndex];
    const overItem = items[overIndex];

    // Swap positions
    const newItems = items.map(item => {
      if (item.id === activeItem.id) {
        return { ...item, row: overItem.row, col: overItem.col };
      }
      if (item.id === overItem.id) {
        return { ...item, row: activeItem.row, col: activeItem.col };
      }
      return item;
    });

    onReorder?.(newItems);
  };

  // Calculate total grid height
  // Account for gaps as grid units (1 unit per gap)
  const totalHeight = rows.reduce((sum, row) => sum + row.height + GRID_CONFIG.GAP, 0);
  const gridHeightPx = totalHeight * GRID_CONFIG.ROW_UNIT_HEIGHT;

  const childrenArray = React.Children.toArray(children);

  return (
    <DndContext
      sensors={sensors}
      collisionDetection={closestCenter}
      onDragEnd={handleDragEnd}
    >
      <SortableContext
        items={items.map(item => `card-${item.id}`)}
        strategy={rectSortingStrategy}
      >
        <div className="relative w-full" style={{ minHeight: gridHeightPx }}>
          {/* Debug Grid Lines */}
          {showGrid && (
            <div 
              className="absolute inset-0 pointer-events-none z-0"
              style={{
                backgroundImage: `
                  repeating-linear-gradient(
                    0deg,
                    rgba(59, 130, 246, 0.1) 0px,
                    rgba(59, 130, 246, 0.1) 1px,
                    transparent 1px,
                    transparent ${GRID_CONFIG.ROW_UNIT_HEIGHT}px
                  ),
                  repeating-linear-gradient(
                    90deg,
                    rgba(59, 130, 246, 0.1) 0px,
                    rgba(59, 130, 246, 0.1) 1px,
                    transparent 1px,
                    transparent ${100 / GRID_CONFIG.COLUMNS}%
                  )
                `,
              }}
            />
          )}

          {/* Cards */}
          {items.map((item, index) => {
            // Calculate absolute position
            // Gaps are now part of the grid (1 unit each)
            const yPosition = engine.getRowYPosition(item.row);
            const xPercent = (item.col / GRID_CONFIG.COLUMNS) * 100;
            const widthPercent = (item.width / GRID_CONFIG.COLUMNS) * 100;
            const heightPx = item.height * GRID_CONFIG.ROW_UNIT_HEIGHT;
            const yPositionPx = yPosition * GRID_CONFIG.ROW_UNIT_HEIGHT;

            return (
              <div
                key={item.id}
                className="absolute"
                style={{
                  left: `${xPercent}%`,
                  top: `${yPositionPx}px`,
                  width: `${widthPercent}%`,
                  height: `${heightPx}px`,
                }}
              >
                <DraggableResizableCard
                  id={item.id}
                  width={item.width}
                  height={item.height}
                  onRemove={onRemove}
                  onResize={onResize}
                  isRemovable={!nonRemovableIds.includes(item.id)}
                  isResizable={!nonRemovableIds.includes(item.id)} // Allow resizing all cards except non-removable ones
                  gridConfig={GRID_CONFIG}
                >
                  {childrenArray[index]}
                </DraggableResizableCard>
              </div>
            );
          })}
        </div>
      </SortableContext>
    </DndContext>
  );
}
