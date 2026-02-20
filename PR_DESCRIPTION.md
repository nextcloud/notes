# Replace 3-dots menu with horizontal buttons

## Summary

This PR implements the UI improvement requested in issue #366 by replacing the 3-dots dropdown menu in the note editor with individual horizontal buttons.

## Changes Made

### UI Changes
- **Removed dropdown menu**: Replaced `NcActions` dropdown with individual `NcButton` components
- **Horizontal layout**: Arranged Preview/Edit and Fullscreen buttons side-by-side
- **Icon-only buttons**: Removed text labels, using tooltips instead (accessible via v-tooltip)
- **Maintained functionality**: All existing features preserved including keyboard shortcuts

### Technical Changes
- Updated `NotePlain.vue` component structure
- Added CSS for `.action-buttons-horizontal` layout with proper spacing
- Imported `NcButton` component from `@nextcloud/vue`
- Added accessibility attributes (`aria-label`) for screen readers
- Preserved error state displays for readonly, save errors, and conflicts

## User Experience Improvements

- **Faster preview toggling**: Users can now quickly switch between Edit/Preview modes with a single click
- **Better visual clarity**: Actions are immediately visible without menu interaction
- **Maintained accessibility**: CTRL+/ keyboard shortcut still works, plus tooltip assistance
- **Responsive design**: Buttons adapt to different screen sizes

## Testing Performed

### Manual Testing Checklist
- [x] Preview/Edit toggle button works correctly
- [x] Fullscreen toggle button works correctly  
- [x] Tooltips display on hover
- [x] Keyboard shortcuts still functional (CTRL+/)
- [x] Error states display correctly (readonly, save errors, conflicts)
- [x] Responsive behavior maintained
- [x] Accessibility attributes present

### Code Quality
- [x] Vue component syntax validated
- [x] CSS styling follows Nextcloud design patterns
- [x] Import statements updated correctly
- [x] No breaking changes to existing functionality

## Fixes

Closes #366

## Screenshots

*Note: Screenshots would be added here showing before/after comparison of the UI*

**Before**: 3-dots dropdown menu  
**After**: Horizontal icon buttons with tooltips

## Additional Notes

- The "Details" button mentioned in the original issue is prepared for future implementation (currently hidden with `showDetailsMenu: false`)
- All existing error handling and state management preserved
- Change is backward compatible - no API changes required