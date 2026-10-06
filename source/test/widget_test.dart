import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:kids_world/widgets/content_server_config_dialog.dart';

void main() {
  testWidgets('ContentServerConfigDialog smoke test', (WidgetTester tester) async {
    await tester.pumpWidget(
      const MaterialApp(
        home: Scaffold(
          body: ContentServerConfigDialog(),
        ),
      ),
    );
    expect(find.text('Cấu Hình Domain Máy Chủ'), findsOneWidget);
    expect(find.byType(TextField), findsOneWidget);
  });
}
