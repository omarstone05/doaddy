import React, { useState, useRef, useEffect, useCallback } from 'react';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { X, GripVertical, Maximize2 } from 'lucide-react';
import { GridEngine } from '@/lib/gridEngine';

export function DraggableResizableCard({ 
  id, 
  children, 
  width, 
  height, 
  onRemove, 
  onResize,
  isRemovable = true,
  isResizable = true,
  gridConfig
}) {
  const [isResizing, setIsResizing] = useState(false);
  const resizeStartRef = useRef(null);
  const containerRef = useRef(null);

  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
    isDragging,
  } = useSortable({ 
    id: `card-${id}`,
    disabled: isResizing,
  });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
    opacity: isDragging ? 0.5 : 1,
  };

  useEffect(() => {
    if (!isResizing) return;

    const handleResizeMove = (moveEvent) => {
      if (!resizeStartRef.current) return;

      const deltaX = moveEvent.clientX - resizeStartRef.current.startX;
      const deltaY = moveEvent.clientY - resizeStartRef.current.startY;

      // Find the grid container to calculate actual sizes
      let gridContainer = containerRef.current?.closest('[data-grid-container]');
      if (!gridContainer) {
        gridContainer = containerRef.current?.parentElement?.parentElement;
      }
      
      const containerWidth = gridContainer?.offsetWidth || 1200;
      const unitWidth = containerWidth / gridConfig.COLUMNS;
      const unitHeight = gridConfig.ROW_UNIT_HEIGHT;

      let newWidth = resizeStartRef.current.startWidth;
      let newHeight = resizeStartRef.current.startHeight;

      if (resizeStartRef.current.direction.includes('right')) {
        const widthDelta = Math.round(deltaX / unitWidth);
        // Snap to 1-unit increments (grid units)
        const snappedDelta = Math.round(widthDelta);
        newWidth = GridEngine.snapToUnit(
          resizeStartRef.current.startWidth + snappedDelta,
          gridConfig.MIN_WIDTH,
          gridConfig.MAX_WIDTH
        );
      }

      if (resizeStartRef.current.direction.includes('bottom')) {
        const heightDelta = Math.round(deltaY / unitHeight);
        // Snap to 1-unit increments (grid units)
        const snappedDelta = Math.round(heightDelta);
        newHeight = GridEngine.snapToUnit(
          resizeStartRef.current.startHeight + snappedDelta,
          gridConfig.MIN_HEIGHT,
          gridConfig.MAX_HEIGHT
        );
      }

      // Only call onResize if values actually changed
      if (newWidth !== resizeStartRef.current.currentWidth || newHeight !== resizeStartRef.current.currentHeight) {
        resizeStartRef.current.currentWidth = newWidth;
        resizeStartRef.current.currentHeight = newHeight;
        onResize?.(id, newWidth, newHeight);
      }
    };

    const handleResizeEnd = () => {
      setIsResizing(false);
      resizeStartRef.current = null;
    };

    document.addEventListener('mousemove', handleResizeMove);
    document.addEventListener('mouseup', handleResizeEnd);

    return () => {
      document.removeEventListener('mousemove', handleResizeMove);
      document.removeEventListener('mouseup', handleResizeEnd);
    };
  }, [isResizing, id, onResize, gridConfig]);

  const handleResizeStart = (e, direction) => {
    e.stopPropagation();
    e.preventDefault();
    
    resizeStartRef.current = {
      startX: e.clientX,
      startY: e.clientY,
      startWidth: width,
      startHeight: height,
      currentWidth: width,
      currentHeight: height,
      direction
    };
    
    setIsResizing(true);
  };

  return (
    <div
      ref={(node) => {
        setNodeRef(node);
        containerRef.current = node;
      }}
      style={style}
      className="relative h-full w-full group"
      data-grid-container
    >
      {/* Card Container */}
      <div className="h-full w-full overflow-hidden">
        {/* Drag Handle & Remove Button */}
        <div className="absolute top-2 right-2 z-10 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
          <button
            {...attributes}
            {...listeners}
            className="p-1.5 bg-white hover:bg-gray-100 rounded border border-gray-300 cursor-grab active:cursor-grabbing shadow-sm"
            title="Drag to reorder"
          >
            <GripVertical className="h-4 w-4 text-gray-600" />
          </button>
          
          {isRemovable && (
            <button
              onClick={(e) => {
                e.stopPropagation();
                onRemove?.(id);
              }}
              className="p-1.5 bg-white hover:bg-red-50 rounded border border-gray-300 shadow-sm"
              title="Remove card"
            >
              <X className="h-4 w-4 text-red-500" />
            </button>
          )}

          {/* Size indicator */}
          <div className="px-2 py-1 bg-gray-900 text-white text-xs rounded shadow-sm font-mono">
            {width}×{height}
          </div>
        </div>

        {/* Content */}
        <div className="h-full w-full overflow-auto">
          {children}
        </div>

        {/* Resize Handles - only show if resizable */}
        {isResizable && (
          <>
            <div
              className="absolute bottom-0 right-0 w-6 h-6 cursor-se-resize opacity-0 group-hover:opacity-100 transition-opacity bg-blue-500 rounded-tl flex items-center justify-center z-10"
              onMouseDown={(e) => handleResizeStart(e, 'bottom-right')}
              title="Drag to resize"
            >
              <Maximize2 className="h-3 w-3 text-white" />
            </div>

            {/* Right edge resize */}
            <div
              className="absolute top-0 right-0 w-2 h-full cursor-ew-resize opacity-0 group-hover:opacity-50 hover:opacity-100 hover:bg-blue-400 transition-opacity z-10"
              onMouseDown={(e) => handleResizeStart(e, 'right')}
            />

            {/* Bottom edge resize */}
            <div
              className="absolute bottom-0 left-0 w-full h-2 cursor-ns-resize opacity-0 group-hover:opacity-50 hover:opacity-100 hover:bg-blue-400 transition-opacity z-10"
              onMouseDown={(e) => handleResizeStart(e, 'bottom')}
            />
          </>
        )}
      </div>
    </div>
  );
}
