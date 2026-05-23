# AI Model Management System - Complete Usage Guide

## Overview
The AI Model Management system allows you to:
- Upload and manage datasets
- Create experiments with your models
- Monitor training progress in real-time
- Track model versions and performance
- Compare experiment results
- Deploy and monitor models

---

## Part 1: Getting Started

### Prerequisites
1. Be logged in to the system
2. Have the AI Model Management permission
3. Have access to sample data (CSV, JSON, Excel, or Parquet files)

---

## Part 2: Step-by-Step Complete Workflow

### Step 1: Upload Your First Dataset

**Why:** Before you can train models, you need data. Datasets are stored files that you'll use across multiple experiments.

**How:**

1. Navigate to **AI Model Management** → **Datasets** (or go to `/ai/datasets`)
2. Click the blue **"Upload Dataset"** button
3. Fill in the form:
   - **Dataset Name**: Give it a meaningful name (e.g., "Customer Purchase Data")
   - **Dataset Type**: Choose one:
     - `training` - For model training
     - `validation` - For validation during training
     - `test` - For final testing
     - `custom` - For other uses
   - **Description** (optional): Add notes about the dataset

4. **Upload your file:**
   - Supported formats: CSV, JSON, Parquet, XLSX
   - Maximum size: 100MB
   - Click the upload area or drag-and-drop your file

5. Click **"Upload Dataset"**

**Expected Result:**
- File is stored on the server
- CSV files are analyzed automatically:
  - Number of rows extracted
  - Number of features (columns) extracted
  - First 5 rows shown as preview
- You're redirected to the dataset detail page

**Example CSV for Testing:**
```csv
id,age,income,credit_score,employment_status,purchased
1,25,45000,700,employed,yes
2,35,65000,750,employed,yes
3,22,30000,650,unemployed,no
4,45,85000,800,employed,yes
5,28,50000,720,employed,no
```

Save this as `sample_data.csv` and upload it.

---

### Step 2: Create Your First Model

**Why:** Models are what you train. They represent your machine learning algorithms.

**How:**

1. Go to **AI Model Management** → **Models** (or `/ai/models`)
2. Click **"+ Create Model"** or use the quick create form at the top
3. Fill in the details:
   - **Model Name**: e.g., "Customer Churn Prediction"
   - **Type**: Choose the ML algorithm type:
     - `classification` - For yes/no or category predictions
     - `regression` - For numeric predictions
     - `clustering` - For grouping data
     - `nlp` - For text analysis
   - **Description**: What does it do?

4. Click **"Create"**

**Expected Result:**
- Model is created and added to your dashboard
- Shows with 0% accuracy (no training yet)
- Ready for experiments

---

### Step 3: Create Your First Experiment

**Why:** Experiments train your model on specific datasets. One model can have multiple experiments with different parameters.

**How:**

1. Go to **AI Model Management** → **Experiments** (or `/ai/experiments`)
2. Click **"+ Start Experiment"** button
3. Select a model from the dropdown
4. Choose a dataset you uploaded earlier
5. Click **"Create Experiment"**

**Expected Result:**
- Experiment is created with status "pending"
- Shows the model name and dataset
- Ready for training runs

---

### Step 4: Start Your First Training Job

**Why:** Training runs are where the actual learning happens. This is where you configure how the model should learn.

**How:**

1. Go to **AI Model Management** → **Training Jobs** (or `/ai/training-runs`)
2. Click **"Start New Job"** button
3. You'll see a dropdown to select an experiment (should show the one you just created)
4. Configure training parameters:

   **Training Parameters:**
   - **Epochs**: How many times to go through the data
     - Default: 10
     - Recommendation: 10-50 for small datasets
     - Range: 1-1000
   
   - **Batch Size**: How many samples to process at once
     - Default: 32
     - Recommendation: 16-128
     - Range: 1-1024
   
   - **Learning Rate**: How quickly the model learns
     - Default: 0.001
     - Recommendation: Keep default or lower
     - Range: 0.00001-1
     - Lower values = slower but more stable learning
     - Higher values = faster but may overshoot

5. Click **"Start Training"**

**Parameter Recommendations for Beginners:**
```
Small Dataset (< 1000 rows):
- Epochs: 20
- Batch Size: 16
- Learning Rate: 0.001

Medium Dataset (1000-10000 rows):
- Epochs: 15
- Batch Size: 32
- Learning Rate: 0.001

Large Dataset (> 10000 rows):
- Epochs: 10
- Batch Size: 64
- Learning Rate: 0.0005
```

**Expected Result:**
- Training job is queued
- Status shows "queued" → "running" → "completed"
- Progress bar fills from 0% to 100%
- Charts start showing accuracy and loss trends

---

### Step 5: Monitor Training Progress

**Why:** Watch your model train in real-time to ensure it's learning properly.

**How:**

1. After starting a training job, you're automatically on the monitoring page
2. Or: Go to **Training Jobs** and click on any running job

**What You'll See:**

- **Progress Bar**: Shows 0-100% completion
- **Accuracy Chart**: Line chart showing accuracy increasing over time
- **Loss Chart**: Line chart showing loss (error) decreasing over time
- **Current Metrics**:
  - Accuracy: How correct the model is (0-100%)
  - Loss: Error rate (lower is better)
  - Epochs: How many times through the data
  - Batch Size: Samples per update
- **Status Badge**: queued → running → completed

**What to Look For:**

✅ **Good Signs:**
- Accuracy increasing over time
- Loss decreasing over time
- Progress bar smoothly advancing
- Final accuracy > 70%

❌ **Warning Signs:**
- Accuracy stuck at same value
- Loss increasing (overfitting)
- Progress bar not moving (may be stuck)

**During Training:**
- You can cancel the job by clicking **"Cancel"**
- Updates refresh every 2 seconds automatically
- Page auto-reloads when complete

---

### Step 6: View Results in Dashboard

**Why:** See your model's performance at a glance after training.

**How:**

1. After training completes, go to **Models** → Click on your model
2. Or: Go to **Experiments** → Click on your experiment

**What You'll See:**

**Model Dashboard Shows:**
- **Best Accuracy**: Highest accuracy achieved (%)
- **Avg Accuracy**: Average across all experiments (%)
- **Experiments**: How many times you've trained the model
- **Versions**: Different model checkpoints created
- **Accuracy Trend Chart**: Line chart of accuracy over all experiments
- **Loss Trend Chart**: Line chart of loss over all experiments
- **Model Versions Table**: List of saved versions
- **Recent Experiments**: Your latest training runs

**Experiment Detail Shows:**
- **Status**: Current training status
- **Best/Avg Accuracy**: Performance metrics
- **Training Runs Table**: All runs for this experiment
- **Accuracy Progress Chart**: How accuracy improved during training
- **Loss Progress Chart**: How loss decreased during training

---

## Part 3: Common Workflows

### Workflow 1: Compare Two Experiments

**Goal:** See which experiment performed better

**Steps:**

1. Go to **Experiments**
2. Click on any experiment
3. Look for a **"Compare"** button or dropdown
4. Select another experiment to compare with
5. View side-by-side metrics:
   - Which one has higher best accuracy
   - Which one has lower loss
   - Which one is "better" (shown with indicator)

---

### Workflow 2: Use Dataset in Multiple Experiments

**Goal:** Test the same dataset with different models

**Steps:**

1. Upload dataset (Step 1 above)
2. Create Model A (Step 2)
3. Create Experiment using dataset (Step 3) → Train (Step 4)
4. Create Model B
5. Create new Experiment using same dataset with Model B
6. Train Model B
7. Compare results in dashboards

---

### Workflow 3: Improve Model Performance

**Goal:** Get better accuracy

**Steps:**

1. Check current model accuracy on dashboard
2. Try one change at a time:
   - **More Epochs**: Increase from 10 to 20
   - **Different Batch Size**: Change from 32 to 16 or 64
   - **Different Learning Rate**: Change from 0.001 to 0.0005
   - **Different Data**: Use a larger or different dataset
3. Create new experiment with the change
4. Train and compare results
5. If better, keep the change and try another
6. If worse, revert and try something else

---

## Part 4: Data Preparation Tips

### CSV Format Requirements

**Required:**
- Headers in first row (column names)
- Consistent data types per column
- UTF-8 encoding

**Example Good CSV:**
```csv
feature1,feature2,feature3,target
1.5,2.3,high,positive
2.1,1.8,low,negative
1.9,2.5,high,positive
```

**Example Bad CSV:**
```csv
f1,f2,f3
1.5,2.3,high
2.1,missing,low
1.9,2.5,high
```
❌ Inconsistent data (missing value) can cause issues

### Recommended Dataset Size

| Model Type | Minimum Rows | Recommended | Ideal |
|-----------|-------------|-------------|-------|
| Classification | 50 | 500+ | 5000+ |
| Regression | 50 | 500+ | 5000+ |
| NLP | 100 | 1000+ | 10000+ |
| Clustering | 100 | 1000+ | 5000+ |

---

## Part 5: Understanding the Metrics

### Accuracy (%)
- **What it is**: Percentage of correct predictions
- **Range**: 0-100%
- **Good Value**: Depends on problem, but 70%+ is usually good
- **Formula**: Correct predictions / Total predictions × 100

### Loss
- **What it is**: Total error of the model
- **Range**: 0 to infinity (lower is better)
- **Good Value**: As low as possible
- **Interpretation**: 
  - High loss = model is confused
  - Low loss = model is confident and correct

### Epochs
- **What it is**: Number of complete passes through the dataset
- **Effect**: More epochs = potentially better accuracy but slower
- **Problem**: Too many epochs = overfitting (memorizing data)

### Batch Size
- **What it is**: Number of samples processed before updating weights
- **Effect**: 
  - Larger batch = faster training, less memory
  - Smaller batch = slower training, noisier learning
- **Typical Values**: 16, 32, 64, 128

### Learning Rate
- **What it is**: How much to adjust weights during training
- **Effect**:
  - Higher = faster learning but may miss optimal
  - Lower = slower learning but more stable
- **Typical Values**: 0.001, 0.0005, 0.01

---

## Part 6: Troubleshooting

### Problem: "No data" showing in charts

**Causes:**
- Training hasn't completed yet (wait for progress to reach 100%)
- Training job was cancelled
- No experiments created yet

**Solution:**
1. Create an experiment (Step 3)
2. Start a training job (Step 4)
3. Wait for it to complete
4. Refresh the page

---

### Problem: Training job stuck at 0%

**Causes:**
- System overloaded
- Long training time
- Job crashed silently

**Solution:**
1. Wait 30 seconds for queue to process
2. Cancel the job and restart
3. Try with fewer epochs (10 instead of 50)
4. Check browser console for errors (F12)

---

### Problem: Accuracy shows 0%

**Causes:**
- Training just started (needs time to process)
- Model hasn't learned anything (wrong parameters)
- Data quality issue

**Solution:**
1. Wait for training to complete (progress = 100%)
2. If still 0%, try:
   - Increase epochs to 20
   - Check dataset has target column
   - Simplify parameters

---

### Problem: CSV file won't upload

**Causes:**
- File too large (> 100MB)
- Wrong format (not CSV/JSON/Parquet/Excel)
- Browser cache issue

**Solution:**
1. Check file size: Right-click file → Properties
2. Check file extension: Must be .csv, .json, .parquet, or .xlsx
3. Try uploading different file
4. Clear browser cache and try again

---

## Part 7: Best Practices

### ✅ Do's
- Start with small datasets and increase size gradually
- Use meaningful names for models and datasets
- Keep experiments organized with descriptions
- Try one parameter change at a time
- Monitor training in real-time (first time)
- Use test dataset for final evaluation

### ❌ Don'ts
- Upload massive files (> 100MB) initially
- Change multiple parameters at once
- Use incomplete or messy data
- Create thousands of experiments at once
- Assume high accuracy = production ready
- Ignore the loss chart (accuracy isn't everything)

---

## Part 8: Next Steps After Training

### If Accuracy is Good (> 80%):
1. Save this model version
2. Test on test dataset
3. Consider deploying (Deployments section)
4. Document the configuration

### If Accuracy is Poor (< 50%):
1. Check data quality
2. Try different model type
3. Increase dataset size
4. Adjust hyperparameters
5. Collect better data

### If Accuracy is Medium (50-80%):
1. Try increasing epochs (20-30)
2. Try decreasing learning rate (0.0005)
3. Try different batch size (16 or 64)
4. Add more training data
5. Try data augmentation

---

## Part 9: Quick Reference Commands

### Navigation
| Page | URL |
|------|-----|
| Datasets | `/ai/datasets` |
| Upload Dataset | `/ai/datasets/create` |
| Models | `/ai/models` |
| Experiments | `/ai/experiments` |
| Training Jobs | `/ai/training-runs` |
| Deployments | `/ai/deployments` |

### Default Parameters
```
Epochs: 10
Batch Size: 32
Learning Rate: 0.001
File Size Limit: 100MB
Supported Formats: CSV, JSON, Parquet, XLSX
```

---

## Part 10: Example Complete Workflow (Copy-Paste Instructions)

### Sample Data to Use:
Create a file named `test_data.csv`:
```csv
age,income,credit_score,purchase_history,approved
25,35000,650,5,no
35,65000,750,20,yes
22,28000,600,2,no
45,85000,800,50,yes
28,50000,720,15,yes
31,72000,780,25,yes
19,22000,580,0,no
52,95000,820,75,yes
26,45000,700,10,yes
38,68000,760,30,yes
```

### Full Walkthrough:

**Time Required: 5-10 minutes**

1. **Upload Dataset** (2 min)
   - Go to `/ai/datasets/create`
   - Name: "Loan Approvals"
   - Type: "training"
   - Upload the CSV above
   - ✅ Dataset created

2. **Create Model** (1 min)
   - Go to `/ai/models`
   - Name: "Loan Predictor"
   - Type: "classification"
   - ✅ Model created

3. **Create Experiment** (1 min)
   - Go to `/ai/experiments`
   - Select "Loan Predictor" model
   - Select "Loan Approvals" dataset
   - ✅ Experiment created

4. **Start Training** (2 min)
   - Go to `/ai/training-runs/create`
   - Epochs: 20
   - Batch Size: 32
   - Learning Rate: 0.001
   - Click "Start Training"
   - ✅ Watch progress bar fill to 100%

5. **View Results** (2 min)
   - Charts show accuracy and loss
   - Go to `/ai/models` to see dashboard
   - Best Accuracy should now show a value
   - ✅ Complete!

---

## Support

If you encounter issues:
1. Check that you're logged in
2. Ensure you have AI Model Management permission
3. Check browser console (F12) for errors
4. Try a different browser
5. Check that Laravel is running: `php artisan serve`

**Debug Checklist:**
- [ ] Logged in?
- [ ] Permission granted?
- [ ] Dataset uploaded?
- [ ] Model created?
- [ ] Experiment created?
- [ ] Training started?
- [ ] Waited for completion?

---

**Last Updated: 2026-04-20**
**Version: 1.0**
