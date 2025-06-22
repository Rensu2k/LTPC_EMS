# LTPC EMS Batch System Implementation

## Overview

The LTPC EMS now includes an automated batch system that manages program enrollments with a maximum of **25 trainees per batch**. When a batch reaches its capacity, new enrollments are automatically assigned to the next available batch.

## Key Features

### 1. Automatic Batch Management

-   **Maximum Capacity**: Each program batch can hold up to 25 trainees
-   **Auto-Assignment**: When current batch is full, system automatically assigns new enrollments to the next batch
-   **Batch Tracking**: Each program tracks its current active batch number

### 2. Database Changes

#### Programs Table

New columns added:

-   `max_enrollments` (integer, default: 25) - Maximum trainees per batch
-   `current_batch` (integer, default: 1) - Current active batch number

#### TraineeEnrollments Table

Existing column enhanced:

-   `batch` (integer) - Batch number for each enrollment

### 3. Model Enhancements

#### Program Model

New methods added:

-   `getCurrentBatchEnrollmentCount()` - Get enrollment count for current batch
-   `isCurrentBatchFull()` - Check if current batch has reached capacity
-   `getNextAvailableBatch()` - Get the next available batch for enrollment
-   `advanceBatchIfFull()` - Advance to next batch if current is full

#### TraineeEnrollment System

Enhanced enrollment logic:

-   Automatically determines appropriate batch for new enrollments
-   Prevents overbooking of batches
-   Maintains enrollment history with batch information

### 4. User Interface Updates

#### Programs Page

-   Added "Current Batch" column showing:
    -   Active batch number (e.g., "Batch 2")
    -   Current batch enrollment count (e.g., "15 / 25 enrolled")

#### Enrollment Modal

-   Added batch system information notice
-   Explains 25-person limit per batch
-   Shows auto-assignment functionality

#### Program Creation Modal

-   Replaced editable "Max Enrollments" field with fixed display showing "25 trainees (Fixed)"
-   Added explanatory text about batch system
-   Value is locked at 25 and cannot be changed

### 5. Enrollment Flow

#### When Enrolling a Trainee:

1. System checks current batch enrollment count
2. If current batch has space (< 25), assigns to current batch
3. If current batch is full (= 25), advances to next batch
4. New trainee is enrolled in the appropriate batch
5. Success message indicates which batch the trainee was assigned to

#### Example Enrollment Messages:

-   Normal enrollment: "Trainee successfully enrolled in Web Development (Batch 1)"
-   Batch advancement: "Trainee successfully enrolled in Web Development (Batch 2). Previous batch was full, enrolled in next available batch."

### 6. Migration Details

#### Migration File: `2025_06_22_092037_set_default_max_enrollments_for_programs.php`

-   Adds `max_enrollments` column (default: 25) if not exists
-   Adds `current_batch` column (default: 1) if not exists
-   Updates existing programs with default values

#### Backward Compatibility

-   Existing enrollments retain their batch assignments
-   Programs without batch info get default values
-   No disruption to existing functionality

### 7. Technical Implementation

#### Controller Logic (TraineeEnrollmentController)

```php
// Determine the batch for enrollment
$assignedBatch = $validated['batch'] ?? $program->getNextAvailableBatch();

// Check if the determined batch is full
if ($program->getCurrentBatchEnrollmentCount() >= $program->max_enrollments && $assignedBatch == $program->current_batch) {
    // Current batch is full, advance to next batch
    $program->advanceBatchIfFull();
    $assignedBatch = $program->current_batch;
}
```

#### Frontend Data Structure

Programs now include:

-   `current_batch`: Current batch number
-   `current_batch_count`: Number of enrollments in current batch
-   `max_enrollments`: Maximum enrollments per batch (default: 25)

### 8. Benefits

1. **Optimal Class Sizes**: Ensures manageable class sizes for better learning
2. **Automatic Management**: No manual intervention required for batch assignments
3. **Clear Organization**: Easy tracking of different training cohorts
4. **Scalability**: System can handle unlimited batches per program
5. **Historical Tracking**: Complete enrollment history with batch information

### 9. User Experience

#### For Officers:

-   Clear visibility of batch status in Programs table
-   Automatic batch assignment during enrollment
-   Informative messages about batch assignments

#### For Trainees:

-   Transparent assignment to appropriate batch
-   No impact on enrollment process
-   Clear batch identification in enrollment records

### 10. Future Enhancements

Potential future improvements:

-   Batch scheduling and calendar integration
-   Batch-specific trainer assignments
-   Batch completion tracking
-   Certificate generation by batch
-   Batch performance analytics

## Usage Examples

### Creating a Program

When creating a new program, officers will see:

-   Fixed max enrollments per batch display (always 25)
-   System automatically manages batch progression

### Enrolling Trainees

1. Officer selects trainee and program
2. System automatically assigns to appropriate batch
3. If current batch is full, trainee goes to next batch
4. Officer receives confirmation with batch information

### Viewing Program Status

Programs page shows:

-   Total enrollments across all batches
-   Current active batch number
-   Current batch enrollment count
-   Progress indicators

This batch system ensures efficient program management while maintaining optimal learning conditions for all trainees.
