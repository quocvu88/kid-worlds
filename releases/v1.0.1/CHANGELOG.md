# KidsWorld Release Notes - Version 1.0.1 (Build 2)

**Release Date:** 2026-10-06  
**Package:** `com.example.kids_world` / `kids_world`  
**Target Platform:** Android (Universal APK)  

---

## 🚀 What's New & Improvements

### 🎨 Coloring Game (`ColoringGameScreen`)
- **New Eraser Tool**: Replaced standard icon with a recognizable eraser graphic (`Icons.cleaning_services_rounded`) and high-contrast styling.
- **Ergonomic Bottom Palette**: Moved color palette to the bottom edge of the screen for natural, thumb-friendly touch interactions.
- **Actual-Size Brush Selectors**: Right sidebar now renders circular previews directly proportional to the brush thickness (Small, Medium, Large, Extra Large).
- **Zero Overflow Guarantee**: Refactored sidebars with responsive scroll views (`SingleChildScrollView`) and compact spacing, eliminating RenderFlex bottom overflow errors in landscape mode.
- **Distraction-Free Canvas**: Cleaned up unneeded UI text badges and descriptive clutter to keep focus solely on creative drawing.

### 📚 Flashcard Learning Screen (`FlashcardLearningScreen`)
- **Immersive Fullscreen Presentation**: Removed restrictive card frame borders to maximize visual engagement for children.
- **Fluid Swipe Gestures**: Smooth horizontal swipe navigation between cards without needing clumsy pagination buttons.
- **Consolidated Bottom Controls**: Simple, kid-oriented bottom control bar featuring instant audio pronunciation (flag + speaker) and direct YouTube companion button.
- **2-Column Responsive Detail Modal**: Optimized landscape layout dividing facts, quiz questions, and descriptions into clean, legible cards.

### 🎮 Mini-Games Hub
- Integrated interactive mini-games directly accessible from the learning journey (Coloring Book, Quick Quiz, Matching Game).

### 🛠️ CMS Builder Web
- **3-Tab Modular Pack Form**: Restructured pack authoring into Basic Info, Learning Cards, and Mini-Games.
- **AI Art Prompt Prompts**: Built-in prompts for outline coloring pages and image dimension specifications.

---

## 📦 Release Artifacts
- **APK File**: `KidsWorld-v1.0.1.apk` (Release Build)
- **Target Architecture**: Universal (arm64-v8a, armeabi-v7a, x86_64)
- **Min SDK**: Android 5.0 (API 21)
- **Target SDK**: Android 14 (API 34)
