# AI Model Management - Quick Reference Card

## 🚀 Getting Started (5 Minutes)

### Step 1: Upload Data
```
Go to: /ai/datasets/create
1. Name your dataset
2. Choose type (training/validation/test)
3. Upload CSV/JSON/Parquet/Excel file
✅ Max 100MB
```

### Step 2: Create Model
```
Go to: /ai/models
1. Name your model
2. Select type (classification/regression/clustering/nlp)
✅ Ready to train
```

### Step 3: Create Experiment
```
Go to: /ai/experiments
1. Pick your model
2. Choose your dataset
✅ Link created
```

### Step 4: Start Training
```
Go to: /ai/training-runs
1. Select experiment
2. Set parameters:
   - Epochs: 10-20
   - Batch Size: 32
   - Learning Rate: 0.001
✅ Training started
```

### Step 5: Monitor
```
Watch the progress bar
Wait for 100% completion
Charts auto-update every 2 seconds
✅ Results ready
```

---

## 📊 URLs Cheat Sheet

| Task | URL |
|------|-----|
| Browse Datasets | `/ai/datasets` |
| Upload Dataset | `/ai/datasets/create` |
| View Dataset | `/ai/datasets/[id]` |
| Browse Models | `/ai/models` |
| View Model Details | `/ai/models/[id]` |
| Create Experiment | `/ai/experiments` |
| View Experiment | `/ai/experiments/[id]` |
| Compare Experiments | `/ai/experiments/[id]/compare` |
| Training Jobs | `/ai/training-runs` |
| Start Job | `/ai/training-runs/create` |
| Monitor Job | `/ai/training-runs/[id]` |
| Deployments | `/ai/deployments` |

---

## ⚙️ Default Parameters

```
Epochs
├─ Default: 10
├─ Range: 1-1000
└─ For beginners: 10-20

Batch Size
├─ Default: 32
├─ Range: 1-1024
└─ For beginners: 16-32

Learning Rate
├─ Default: 0.001
├─ Range: 0.00001-1
└─ For beginners: 0.001

File Upload
├─ Max size: 100MB
├─ Formats: CSV, JSON, Parquet, Excel
└─ Encoding: UTF-8
```

---

## ✅ Good Signs During Training

- ✅ Accuracy increasing over time
- ✅ Loss decreasing over time
- ✅ Progress bar advancing smoothly
- ✅ Status: RUNNING
- ✅ Final accuracy > 70%

---

## ❌ Warning Signs

- ❌ Accuracy stuck at same value
- ❌ Loss increasing (overfitting)
- ❌ Progress bar stuck at 0%
- ❌ Status: FAILED
- ❌ Final accuracy < 50%

---

## 🔧 How to Improve Accuracy

| Problem | Try This |
|---------|----------|
| Accuracy too low | Increase epochs to 30 |
| Training too slow | Increase batch size to 64 |
| Loss won't decrease | Decrease learning rate to 0.0005 |
| Overfitting (loss↑) | Decrease epochs to 5 |
| No data in charts | Wait for training to complete |
| Stuck at 0% | Cancel and try again |

---

## 📈 Understanding Metrics

### Accuracy (%)
- Best: 90-100%
- Good: 80%+
- Acceptable: 70%+
- Poor: <50%

### Loss
- Best: <0.1
- Good: 0.1-0.5
- Okay: 0.5-1.0
- Poor: >1.0

### Epochs
- Small (1-5): Might not learn enough
- Medium (10-20): Good balance
- Large (100+): Risk overfitting

### Batch Size
- Small (8-16): More learning updates, slower
- Medium (32-64): Good balance
- Large (128+): Faster but less precise

### Learning Rate
- High (0.1): Fast but may jump over best solution
- Medium (0.001): Good balance (recommended)
- Low (0.00001): Slow but very stable

---

## 🐛 Troubleshooting

### "No data" showing?
- [ ] Training completed? (Check progress = 100%)
- [ ] Experiment created? (Go to /ai/experiments)
- [ ] Training started? (Go to /ai/training-runs)
- [ ] Try refreshing page

### File won't upload?
- [ ] File size < 100MB?
- [ ] Format is .csv/.json/.parquet/.xlsx?
- [ ] Clear browser cache
- [ ] Try different file

### Training stuck?
- [ ] Wait 30 seconds (queue processing)
- [ ] Check browser console (F12)
- [ ] Cancel job and retry
- [ ] Try with fewer epochs

### Accuracy stuck at 0%?
- [ ] Training completed?
- [ ] Check data quality
- [ ] Try different model type
- [ ] Increase dataset size

---

## 💡 Pro Tips

1. **Start small**: Use 100 rows first to test
2. **One change at a time**: Only adjust 1 parameter per experiment
3. **Monitor charts**: Loss should decrease, accuracy should increase
4. **Save good results**: Note the parameters that work
5. **Check loss chart**: High accuracy with high loss = overfitting
6. **Use test data**: Always validate on separate test data
7. **Document params**: Write down what worked for future reference

---

## 🎯 Sample Quick Start Data

Save as `sample.csv`:
```csv
feature1,feature2,feature3,target
1.5,2.3,high,yes
2.1,1.8,low,no
1.9,2.5,high,yes
3.2,2.1,high,yes
1.1,1.5,low,no
2.8,3.0,high,yes
1.3,1.9,low,no
3.5,3.2,high,yes
```

---

## 📱 Mobile Tips

- Use landscape mode for charts
- Tap status badges to see details
- Scroll down for more information
- Charts adjust to screen size automatically
- Real-time updates work on mobile too

---

## 🔒 Requirements

- Must be logged in
- Need AI Model Management permission
- Browser: Chrome, Firefox, Safari, Edge (modern versions)
- Internet connection required
- Server must be running: `php artisan serve`

---

## 📞 Common Questions

**Q: How long does training take?**
A: 2-5 minutes typically. Larger datasets take longer.

**Q: Can I stop training?**
A: Yes! Click "Cancel" button while running.

**Q: Can I use same dataset twice?**
A: Yes! You can use one dataset in multiple experiments.

**Q: Will my data be deleted?**
A: Only if you click delete. Otherwise it's permanent.

**Q: Can I export results?**
A: Currently shows in dashboard. Screenshots or manual export.

**Q: What's a "version"?**
A: A checkpoint of your model at a point in time.

---

## 🎓 Learning Path

### Beginner (First 30 min)
1. Upload sample dataset
2. Create one model
3. Run one training job
4. Watch it complete
5. View dashboard

### Intermediate (30-60 min)
1. Try different parameters
2. Compare two experiments
3. Test with own data
4. Adjust based on results

### Advanced (1-2 hours)
1. Create multiple models
2. Run A/B tests (compare)
3. Optimize parameters
4. Document best practices
5. Deploy model

---

## 🚀 Next Steps After Training

✅ **Accuracy > 80%**
- Save model version
- Create more experiments to confirm
- Test on test dataset
- Consider deploying

⚠️ **Accuracy 50-80%**
- Try increasing epochs
- Change batch size
- Collect more data
- Try different model

❌ **Accuracy < 50%**
- Check data quality (duplicates, missing values)
- Try completely different model type
- Get more training data
- Check target variable is correct

---

## 📋 Pre-Training Checklist

Before starting training:
- [ ] Dataset uploaded?
- [ ] Model created?
- [ ] Experiment created linking both?
- [ ] Parameters look reasonable?
- [ ] Have 5+ minutes for training?
- [ ] Browser tab will stay open?

---

## 🎯 Success Metrics

| Metric | Target | Status |
|--------|--------|--------|
| Accuracy | > 70% | |
| Loss | < 0.5 | |
| Training Time | < 5 min | |
| Data Preview | Loaded | |
| Charts | Visible | |

---

**Print this and keep it handy! Last updated: 2026-04-20**
