import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class AppColors {
  static const Color primary = Color(0xFFFF5E7E); // Warm Coral
  static const Color primaryDark = Color(0xFFE04364);
  static const Color coralRed = Color(0xFFEF4444); // Red / Error
  static const Color secondary = Color(0xFF38BDF8); // Sky Blue
  static const Color accent = Color(0xFFFBBF24); // Amber
  static const Color success = Color(0xFF10B981); // Emerald
  static const Color purple = Color(0xFF8B5CF6); // Indigo / Violet
  static const Color background = Color(0xFFF8FAFC); // Slate 50
  static const Color cardBg = Colors.white;
  static const Color surfaceMuted = Color(0xFFF1F5F9); // Slate 100
  static const Color border = Color(0xFFE2E8F0); // Slate 200
  static const Color borderFocused = Color(0xFF94A3B8); // Slate 400

  // Slate text hierarchy for clean, slim typography
  static const Color textDark = Color(0xFF0F172A); // Slate 900
  static const Color textBody = Color(0xFF334155); // Slate 700
  static const Color textLight = Color(0xFF64748B); // Slate 500
  static const Color textMuted = Color(0xFF94A3B8); // Slate 400

  static const Color nightSky = Color(0xFF0F172A);
  static const Color moonYellow = Color(0xFFFDE047);

  static const List<Color> avatarColors = [
    Color(0xFFF472B6), // Pink
    Color(0xFFFBBF24), // Amber
    Color(0xFFFB7185), // Rose
    Color(0xFF38BDF8), // Sky
    Color(0xFF34D399), // Emerald
    Color(0xFF818CF8), // Indigo
  ];
}

class AppTextStyles {
  // Config & Parent Settings Typography: Slim, neat, legible
  static TextStyle configTitle = GoogleFonts.plusJakartaSans(
    fontSize: 16,
    fontWeight: FontWeight.w600,
    color: AppColors.textDark,
    letterSpacing: -0.2,
  );

  static TextStyle configSection = GoogleFonts.plusJakartaSans(
    fontSize: 11.5,
    fontWeight: FontWeight.w600,
    color: AppColors.textLight,
    letterSpacing: 0.3,
  );

  static TextStyle configLabel = GoogleFonts.plusJakartaSans(
    fontSize: 12.5,
    fontWeight: FontWeight.w500,
    color: AppColors.textBody,
  );

  static TextStyle configBody = GoogleFonts.plusJakartaSans(
    fontSize: 13,
    fontWeight: FontWeight.w400,
    color: AppColors.textBody,
  );

  static TextStyle configValue = GoogleFonts.plusJakartaSans(
    fontSize: 13,
    fontWeight: FontWeight.w600,
    color: AppColors.textDark,
  );

  static TextStyle configCaption = GoogleFonts.plusJakartaSans(
    fontSize: 11,
    fontWeight: FontWeight.w400,
    color: AppColors.textMuted,
  );

  static TextStyle configButton = GoogleFonts.plusJakartaSans(
    fontSize: 13,
    fontWeight: FontWeight.w600,
    letterSpacing: 0.1,
  );

  // App Display Typography
  static TextStyle kidHeadline = GoogleFonts.plusJakartaSans(
    fontSize: 19,
    fontWeight: FontWeight.w700,
    color: AppColors.textDark,
  );

  static TextStyle kidTitle = GoogleFonts.plusJakartaSans(
    fontSize: 15,
    fontWeight: FontWeight.w600,
    color: AppColors.textDark,
  );

  static TextStyle kidSubTitle = GoogleFonts.plusJakartaSans(
    fontSize: 14,
    fontWeight: FontWeight.w600,
    color: AppColors.textDark,
  );

  static TextStyle kidBody = GoogleFonts.plusJakartaSans(
    fontSize: 13,
    fontWeight: FontWeight.w400,
    color: AppColors.textBody,
  );
}

class AppTheme {
  static ThemeData get lightTheme {
    final baseFont = GoogleFonts.plusJakartaSansTextTheme();

    return ThemeData(
      useMaterial3: true,
      scaffoldBackgroundColor: AppColors.background,
      colorScheme: ColorScheme.fromSeed(
        seedColor: AppColors.primary,
        primary: AppColors.primary,
        secondary: AppColors.secondary,
        tertiary: AppColors.accent,
        surface: AppColors.cardBg,
      ),
      textTheme: baseFont.copyWith(
        titleLarge: GoogleFonts.plusJakartaSans(fontSize: 18, fontWeight: FontWeight.w600, color: AppColors.textDark),
        titleMedium: GoogleFonts.plusJakartaSans(fontSize: 15, fontWeight: FontWeight.w600, color: AppColors.textDark),
        bodyMedium: GoogleFonts.plusJakartaSans(fontSize: 13, fontWeight: FontWeight.w400, color: AppColors.textBody),
        bodySmall: GoogleFonts.plusJakartaSans(fontSize: 11.5, fontWeight: FontWeight.w400, color: AppColors.textLight),
      ),
      appBarTheme: AppBarTheme(
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: true,
        iconTheme: const IconThemeData(color: AppColors.textDark, size: 22),
        titleTextStyle: GoogleFonts.plusJakartaSans(
          color: AppColors.textDark,
          fontSize: 17,
          fontWeight: FontWeight.w600,
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          elevation: 0,
          padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 10),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
          textStyle: GoogleFonts.plusJakartaSans(fontSize: 13.5, fontWeight: FontWeight.w600),
        ),
      ),
      cardTheme: CardThemeData(
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: const BorderSide(color: AppColors.border, width: 1),
        ),
        color: AppColors.cardBg,
      ),
    );
  }
}
