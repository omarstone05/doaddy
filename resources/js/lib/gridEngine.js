/**
 * Bento Grid Engine
 * Manages layout calculations for a 48-column (6x) unit-based grid system
 * Gaps between cards are 1 grid unit, allowing precise spacing control
 */

export const GRID_CONFIG = {
  COLUMNS: 48, // 6x the original 8 columns for finer control
  MIN_WIDTH: 4, // Minimum 1 unit in old system = 4 units now (for quick actions)
  MAX_WIDTH: 48, // Maximum width matches column count
  MIN_HEIGHT: 4, // Minimum 1 unit in old system = 4 units now
  MAX_HEIGHT: 48, // Maximum height for consistency
  STANDARD_WIDTH: 8, // Default width for regular cards
  STANDARD_HEIGHT: 8, // Default height for regular cards
  ROW_UNIT_HEIGHT: 37.5, // pixels per unit height (150px / 4 = 37.5px to maintain same visual size)
  GAP: 1, // 1 grid unit between cards (instead of CSS gap)
};

export class GridEngine {
  constructor(cards = []) {
    this.cards = cards;
    this.rows = this.calculateRows();
  }

  /**
   * Calculate row structure based on cards
   */
  calculateRows() {
    const rows = new Map();

    // Group cards by row
    this.cards.forEach(card => {
      const rowIndex = card.row;

      if (!rows.has(rowIndex)) {
        rows.set(rowIndex, {
          index: rowIndex,
          cards: [],
          height: 0,
        });
      }

      rows.get(rowIndex).cards.push(card);
    });

    // Calculate row heights (tallest card in row)
    rows.forEach(row => {
      row.height = Math.max(
        ...row.cards.map(c => c.height),
        GRID_CONFIG.MIN_HEIGHT
      );
    });

    return Array.from(rows.values()).sort((a, b) => a.index - b.index);
  }

  /**
   * Check if a card placement would cause collision
   */
  checkCollision(row, col, width, height, excludeId = null) {
    // Card occupies: row to row+height, col to col+width
    // With gaps, we need to check if there's overlap including gap space
    const cardRowEnd = row + height;
    const cardColEnd = col + width;

    for (const card of this.cards) {
      if (card.id === excludeId) continue;

      const existingRowEnd = card.row + card.height;
      const existingColEnd = card.col + card.width;

      // Check overlap - cards are separated by 1 unit gap
      // So we check if the new card overlaps with existing card + its gap
      if (
        row < existingRowEnd + GRID_CONFIG.GAP &&
        cardRowEnd + GRID_CONFIG.GAP > card.row &&
        col < existingColEnd + GRID_CONFIG.GAP &&
        cardColEnd + GRID_CONFIG.GAP > card.col
      ) {
        return true;
      }
    }

    return false;
  }

  /**
   * Find next available position for a card
   * Scans from top to bottom, left to right to find the first space that fits
   * Accounts for 1-unit gaps between cards
   */
  findNextPosition(width, height, preferredRow = 0) {
    // Start from the preferred row (or 0)
    let startRow = Math.max(0, preferredRow);
    const maxRows = 1000; // Reasonable limit to prevent infinite loops
    
    // Scan from top to bottom
    for (let row = startRow; row < startRow + maxRows; row++) {
      // For each row, scan from left to right
      for (let col = 0; col <= GRID_CONFIG.COLUMNS - width; col++) {
        // Check if this position can accommodate the card
        if (!this.checkCollision(row, col, width, height)) {
          return { row, col };
        }
      }
    }

    // Fallback: place at the end
    const maxRow = this.cards.length > 0
      ? Math.max(...this.cards.map(c => c.row + c.height + GRID_CONFIG.GAP))
      : startRow;
    return { row: maxRow, col: 0 };
  }

  /**
   * Validate card dimensions
   */
  validateCard(card) {
    const errors = [];

    if (card.width < GRID_CONFIG.MIN_WIDTH) {
      errors.push(`Width must be at least ${GRID_CONFIG.MIN_WIDTH} units`);
    }
    if (card.width > GRID_CONFIG.MAX_WIDTH) {
      errors.push(`Width cannot exceed ${GRID_CONFIG.MAX_WIDTH} units`);
    }
    if (card.height < GRID_CONFIG.MIN_HEIGHT) {
      errors.push(`Height must be at least ${GRID_CONFIG.MIN_HEIGHT} units`);
    }
    if (card.height > GRID_CONFIG.MAX_HEIGHT) {
      errors.push(`Height cannot exceed ${GRID_CONFIG.MAX_HEIGHT} units`);
    }
    if (card.col + card.width > GRID_CONFIG.COLUMNS) {
      errors.push('Card extends beyond grid width');
    }

    return {
      valid: errors.length === 0,
      errors,
    };
  }

  /**
   * Snap value to nearest unit
   */
  static snapToUnit(value, min, max) {
    const snapped = Math.round(value);
    return Math.max(min, Math.min(max, snapped));
  }

  /**
   * Calculate absolute Y position for a row
   * Accounts for gaps as grid units
   */
  getRowYPosition(rowIndex) {
    let yPosition = 0;

    for (const row of this.rows) {
      if (row.index >= rowIndex) break;
      // Add row height + gap (1 unit)
      yPosition += row.height + GRID_CONFIG.GAP;
    }

    return yPosition;
  }

  /**
   * Convert old grid units to new 4x grid units
   */
  static scaleToNewGrid(oldValue) {
    return oldValue * 4;
  }

  /**
   * Convert new 4x grid units to old grid units (for display)
   */
  static scaleFromNewGrid(newValue) {
    return newValue / 4;
  }

  /**
   * Auto-layout all cards
   */
  autoLayout(cards) {
    const laid = [];
    const addyCard = cards.find(c => c.id === 'addy-insight-card');
    
    // Place Addy first at [0,0] with 24×8 dimensions
    if (addyCard) {
      laid.push({
        ...addyCard,
        row: 0,
        col: 0,
        width: 24, // 6 columns in old system = 24 in new system
        height: 8, // 8 units high
      });
    }

    // Separate quick actions blocks from regular cards
    const quickActionBlocks = cards.filter(c => 
      c.type === 'quick_actions_block' ||
      c.dashboard_card?.key === 'quick_actions_block' ||
      c.id?.startsWith('quick-actions-block')
    );
    const regularCards = cards.filter(c => 
      c.id !== 'addy-insight-card' && 
      c.type !== 'quick_actions_block' &&
      c.dashboard_card?.key !== 'quick_actions_block' &&
      !c.id?.startsWith('quick-actions-block') &&
      c.type !== 'quick_action' && 
      c.dashboard_card?.key !== 'quick_action'
    );

    // Place quick action blocks - they will be positioned by the auto-layout algorithm
    // They'll fill empty spaces from top to bottom, left to right like regular cards

    // Place quick action blocks and regular cards - they will fill empty spaces from top to bottom, left to right
    // Start from row 0 to ensure we fill all available spaces
    const allPlaceableCards = [...quickActionBlocks, ...regularCards];
    
    for (const card of allPlaceableCards) {
      // Use 8x8 as default size for all regular cards
      // If card has existing dimensions, use them; otherwise default to 8x8
      const width = card.width || GRID_CONFIG.STANDARD_WIDTH;
      const height = card.height || GRID_CONFIG.STANDARD_HEIGHT;
      
      // Ensure dimensions snap to grid (already in grid units, but ensure they're valid)
      const snappedWidth = Math.max(GRID_CONFIG.MIN_WIDTH, Math.min(GRID_CONFIG.MAX_WIDTH, width));
      const snappedHeight = Math.max(GRID_CONFIG.MIN_HEIGHT, Math.min(GRID_CONFIG.MAX_HEIGHT, height));
      
      // Find first available position from top-left
      // This will fill empty spaces efficiently
      const engine = new GridEngine(laid);
      const position = engine.findNextPosition(snappedWidth, snappedHeight, 0);
      
      laid.push({
        ...card,
        row: position.row,
        col: position.col,
        width: snappedWidth,
        height: snappedHeight,
      });
    }

    return laid;
  }
}


