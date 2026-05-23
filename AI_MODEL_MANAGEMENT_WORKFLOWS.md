# AI Model Management - Visual Workflow

## Complete System Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                    START: AI Model Management                       │
└─────────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
                    ┌──────────────────────────┐
                    │  1. UPLOAD DATASET       │
                    │  (/ai/datasets/create)   │
                    └──────────────────────────┘
                              │
                    ┌─────────┴──────────┐
                    │ Select CSV/JSON/   │
                    │ Excel/Parquet      │
                    │ (Max 100MB)        │
                    └────────┬───────────┘
                             │
                    ┌────────▼────────┐
                    │ Metadata        │
                    │ Extracted:      │
                    │ - Row count     │
                    │ - Column count  │
                    │ - Data preview  │
                    └────────┬────────┘
                             │
                             ▼
                    ┌──────────────────────┐
                    │ 2. CREATE MODEL      │
                    │ (/ai/models)         │
                    │ Pick type:           │
                    │ - Classification     │
                    │ - Regression         │
                    │ - Clustering         │
                    │ - NLP                │
                    └──────────┬───────────┘
                               │
                               ▼
                    ┌──────────────────────┐
                    │ 3. CREATE EXPERIMENT │
                    │ (/ai/experiments)    │
                    │ Link:                │
                    │ Model + Dataset      │
                    └──────────┬───────────┘
                               │
                               ▼
                    ┌──────────────────────────┐
                    │ 4. START TRAINING JOB    │
                    │ (/ai/training-runs)      │
                    │ Configure:               │
                    │ - Epochs (10-100)        │
                    │ - Batch Size (16-128)    │
                    │ - Learning Rate (0.0005) │
                    └──────────┬───────────────┘
                               │
                               ▼
                    ┌──────────────────────┐
                    │ TRAINING IN PROGRESS │
                    │ Status: RUNNING      │
                    │ Progress: 0% → 100%  │
                    └──────────┬───────────┘
                               │
                ┌──────────────┴──────────────┐
                │                             │
                ▼                             ▼
    ┌──────────────────┐        ┌──────────────────┐
    │ Monitor Progress │        │ View in Real-time│
    │ - Accuracy ↑     │        │ - Charts         │
    │ - Loss ↓         │        │ - Metrics        │
    │ (Every 2 sec)    │        │ - Update auto    │
    └──────────┬───────┘        └────────┬─────────┘
               │                         │
               └──────────────┬──────────┘
                              │
                              ▼
                    ┌──────────────────────┐
                    │ TRAINING COMPLETE    │
                    │ Progress: 100%       │
                    │ Auto-refresh page    │
                    └──────────┬───────────┘
                               │
                               ▼
                    ┌──────────────────────┐
                    │ 5. VIEW RESULTS      │
                    │ (/ai/models/[id])    │
                    │ Shows:               │
                    │ - Best Accuracy %    │
                    │ - Avg Accuracy %     │
                    │ - Accuracy Trend     │
                    │ - Loss Trend         │
                    │ - Experiment Count   │
                    │ - Version Count      │
                    └──────────┬───────────┘
                               │
                ┌──────────────┴──────────────┐
                │                             │
                ▼                             ▼
    ┌───────────────────────┐    ┌─────────────────────┐
    │ Good Accuracy (>80%)  │    │ Poor Accuracy (<50%)│
    │ ✅ Next Steps:        │    │ ❌ Next Steps:      │
    │ 1. Save Version       │    │ 1. Check Data       │
    │ 2. Compare with       │    │ 2. Try New Model    │
    │    other experiments  │    │ 3. Increase Data    │
    │ 3. Deploy             │    │ 4. Adjust Params    │
    │ 4. Monitor            │    │ 5. Retry            │
    └───────────────────────┘    └─────────────────────┘
                │                        │
                └────────────┬───────────┘
                             │
                             ▼
                    ┌──────────────────────┐
                    │ OPTIONAL:            │
                    │ COMPARE EXPERIMENTS  │
                    │ (/ai/experiments)    │
                    │ - Side-by-side       │
                    │ - Which is better?   │
                    │ - Metrics comparison │
                    └──────────────────────┘
```

---

## Decision Tree: Improving Model Performance

```
                         Accuracy < 70%?
                               │
                ┌──────────────┴──────────────┐
                NO                           YES
                │                             │
                ▼                             ▼
        ✅ Model OK              ❓ What's wrong?
        Ready to deploy              │
                                     ▼
                        ┌────────────────────┐
                        │ Check Loss Chart   │
                        └────────┬───────────┘
                                 │
        ┌────────────────────────┼────────────────────────┐
        │                        │                        │
        ▼                        ▼                        ▼
    Loss High?         Loss Low but Acc Low?   Acc Stuck Same?
        │                        │                   │
        ▼                        ▼                   ▼
    Data issue?        Try different         Check data
    Too noisy           model type            quality
        │                   │                     │
        ▼                   ▼                     ▼
    Get better data    Regression vs       Clean data:
    More rows          Classification      Remove duplicates
    Better quality     NLP model           Fix missing values
        │                   │                     │
        └───────┬───────────┴──────────────────────┘
                │
                ▼
        Retry training
        with new config
```

---

## Data Flow Diagram

```
┌──────────────┐
│  Raw Data    │
│   (CSV/etc)  │
└──────┬───────┘
       │
       ▼
┌──────────────────────┐
│ 1. UPLOAD DATASET    │
│ Parse & Extract      │
│ Metadata             │
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ Dataset Stored       │
│ - Name               │
│ - Type               │
│ - Path               │
│ - Metadata (rows,    │
│   features, preview) │
└──────┬───────────────┘
       │
       ├─ Link to Experiment 1
       ├─ Link to Experiment 2
       └─ Link to Experiment 3
              │ │ │
              ▼ ▼ ▼
        ┌──────────────────┐
        │ 2. EXPERIMENT    │
        │ (Model + Dataset)│
        └─────┬────┬──────┘
              │    │
        Train Job 1 │
              │     │
              ▼     ▼
        ┌─────────────────────┐
        │ 3. TRAINING RUN     │
        │ Parameters:         │
        │ - Epochs            │
        │ - Batch Size        │
        │ - Learning Rate     │
        └─────┬────┬──────────┘
              │    │
              ▼    ▼
        ┌──────────────────┐
        │ Training Progress│
        │ - Metrics logged │
        │ - Charts updated │
        │ - Real-time      │
        └──────┬───────────┘
               │
               ▼
        ┌──────────────────┐
        │ Results Stored   │
        │ - Best Accuracy  │
        │ - Final Loss     │
        │ - Metrics History│
        └──────┬───────────┘
               │
               ▼
        ┌──────────────────┐
        │ Dashboard        │
        │ Updated with     │
        │ New results      │
        └──────────────────┘
```

---

## Timeline: What Happens During Training

```
Time    Status      Progress  Accuracy  Loss      What's Happening
────────────────────────────────────────────────────────────────────
0:00    QUEUED      0%        0%        high      Waiting to start
        
0:05    RUNNING     10%       15%       0.8       Model initializing
        
0:10    RUNNING     20%       25%       0.7       First epoch complete
        
0:15    RUNNING     30%       35%       0.6       Learning accelerating
        
0:20    RUNNING     40%       45%       0.5       Good progress
        
0:25    RUNNING     50%       55%       0.45      Halfway point
        
0:30    RUNNING     60%       65%       0.4       Steady improvement
        
0:35    RUNNING     70%       70%       0.35      Close to completion
        
0:40    RUNNING     80%       75%       0.3       Final adjustments
        
0:45    RUNNING     90%       78%       0.28      Nearly done
        
0:50    RUNNING     99%       80%       0.27      Last epoch
        
1:00    COMPLETED   100%      82%       0.25      ✅ Complete!
```

---

## File Upload Process

```
You select file
      │
      ▼
┌─────────────────┐
│ File Validation │
│ - Size < 100MB? │
│ - Format ok?    │
│   (CSV/JSON/    │
│   Parquet/xlsx) │
└────────┬────────┘
         │
    ┌────┴────┐
    YES       NO
    │         │
    ▼         ▼
Upload    ❌ Error
    │       message
    ▼
┌─────────────────────┐
│ File Stored on      │
│ Server              │
│ Location:           │
│ public/storage/     │
│ datasets/[name]     │
└────────┬────────────┘
         │
    ┌────────────────┐
    │ CSV detected?  │
    └────┬───────────┘
         │
    ┌────┴─────┐
    YES        NO
    │          │
    ▼          ▼
┌─────────┐ Store as-is
│ Parse   │
│ CSV     │
└────┬────┘
     │
     ▼
┌──────────────────┐
│ Extract:         │
│ - Column names   │
│ - Row count      │
│ - First 5 rows   │
│   as preview     │
└────┬─────────────┘
     │
     ▼
┌─────────────────────────────────┐
│ Save Dataset Record with         │
│ Metadata (JSON):                 │
│ {                               │
│   rows: 1000,                   │
│   features: 12,                 │
│   size: "2.5 MB",               │
│   preview: [[...], [...], ...]  │
│ }                               │
└────┬────────────────────────────┘
     │
     ▼
✅ Dataset Ready to Use
```

---

## Training Parameters Explained

```
┌─────────────────────────────────────────────────────────┐
│ EPOCHS: How many times model sees all the data          │
├─────────────────────────────────────────────────────────┤
│ Default: 10                                             │
│ Range: 1-1000                                           │
│                                                         │
│ Epochs = 1:  [Pass 1 through all data]                 │
│ Epochs = 3:  [Pass 1] [Pass 2] [Pass 3]               │
│                                                         │
│ Effect on Training:                                     │
│ Low (1-5):   Fast, but may not learn enough            │
│ Medium (10-20): Good balance                           │
│ High (100+): Slow, risk of overfitting                 │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│ BATCH SIZE: How many samples in each learning update    │
├─────────────────────────────────────────────────────────┤
│ Default: 32                                             │
│ Range: 1-1024                                           │
│                                                         │
│ Dataset = 1000 rows, Batch Size = 32                  │
│ Updates per epoch = 1000 / 32 = ~31 updates           │
│                                                         │
│ Small (8-16):   More updates, slower, noisier         │
│ Medium (32-64): Good balance                          │
│ Large (128+):   Fewer updates, faster, smoother       │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│ LEARNING RATE: How much to change weights each update   │
├─────────────────────────────────────────────────────────┤
│ Default: 0.001                                          │
│ Range: 0.00001 - 1                                      │
│                                                         │
│ Imagine climbing down a hill:                          │
│ High rate (0.1):  Big steps, may jump over valley     │
│ Medium (0.001):   Normal steps, find valley           │
│ Low (0.00001):    Tiny steps, very careful            │
│                                                         │
│ High LR:   Fast learning, may miss optimum            │
│ Medium LR: Good balance (recommended)                 │
│ Low LR:    Slow learning, very stable                 │
└─────────────────────────────────────────────────────────┘
```

---

## Status Meanings

```
Dataset Upload:
┌──────────┬──────────────────────────────────┐
│ PENDING  │ Waiting to be processed          │
│ ACTIVE   │ Ready to use in experiments      │
│ ARCHIVED │ Hidden but not deleted           │
└──────────┴──────────────────────────────────┘

Model Status:
┌──────────┬──────────────────────────────────┐
│ DRAFT    │ Created but never trained        │
│ TRAINED  │ Has training results             │
│ DEPLOYED │ Currently in production          │
│ ARCHIVED │ Hidden but not deleted           │
└──────────┴──────────────────────────────────┘

Experiment Status:
┌──────────┬──────────────────────────────────┐
│ PENDING  │ Created but no training runs     │
│ RUNNING  │ Currently training               │
│ COMPLETED│ Training finished successfully   │
│ FAILED   │ Training encountered error       │
└──────────┴──────────────────────────────────┘

Training Run Status:
┌──────────┬──────────────────────────────────┐
│ QUEUED   │ Waiting for resources            │
│ RUNNING  │ Currently training               │
│ COMPLETED│ Finished successfully            │
│ FAILED   │ Error during training            │
│ CANCELLED│ Manually stopped                 │
└──────────┴──────────────────────────────────┘
```

---

## Quick Troubleshooting Flow

```
            Issue Detected?
                  │
                  ▼
    ┌─────────────────────────┐
    │ What's the problem?     │
    └─────────────────────────┘
               │
    ┌──────────┼──────────┬──────────┐
    │          │          │          │
    ▼          ▼          ▼          ▼
File won't  No data   Training    Accuracy
upload      showing   stuck       stuck low
    │          │          │          │
    ▼          ▼          ▼          ▼
Check:      Check:     Check:      Check:
- Size      - Exp      - Queue     - Data
  < 100MB     exists    process     quality
- Format    - Training - Params    - Loss
  .csv        started   - Epochs     chart
- Browser   - Time     - Check
  cache      elapsed   console
    │          │          │          │
    ▼          ▼          ▼          ▼
Fix:        Fix:       Fix:        Fix:
- Split     - Wait     - Cancel    - Retry
  file        30 sec    & restart   with
- Change    - Create   - Less      better
  format      exp      epochs      data
- Retry     - Start    - Simpler
            training   model
    │          │          │          │
    └──────────┴──────────┴──────────┘
               │
               ▼
          ✅ Problem Solved!
```

---

## Quick Stats Reference

```
ACCURACY INTERPRETATION:
├─ 50-60%   : Random guessing (needs improvement)
├─ 60-70%   : Below average (try different approach)
├─ 70-80%   : Good (acceptable for most uses)
├─ 80-90%   : Very Good (production ready)
└─ 90-100%  : Excellent (check for overfitting)

LOSS INTERPRETATION:
├─ > 1.0    : High (model not learning)
├─ 0.5-1.0  : Medium (still learning)
├─ 0.1-0.5  : Good (converging)
└─ < 0.1    : Very Good (well-trained)

TRAINING TIME ESTIMATES:
├─ Small dataset + few epochs    : 30 sec - 2 min
├─ Medium dataset + 20 epochs    : 2-5 min
├─ Large dataset + many epochs   : 5-30 min
└─ Very large dataset + many     : 30+ min
```

---

## Browser Navigation Map

```
                    DASHBOARD
                        │
        ┌───────────────┼───────────────┐
        │               │               │
        ▼               ▼               ▼
    DATASETS        MODELS         EXPERIMENTS
        │               │               │
   ┌────┴────┐      ┌────┴────┐      ┌────┴────┐
   │          │      │         │      │         │
   ▼          ▼      ▼         ▼      ▼         ▼
 Index     Create  Index     Show   Index     Show
 Show              Compare           Compare
                   Create              Promote
                        │
                        ▼
                   TRAINING RUNS
                        │
                   ┌────┴────┐
                   │          │
                   ▼          ▼
                 Index      Show
                Create    Progress
                         Cancel
```

---

This should help visualize how the entire system works!
