import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:kids_world/core/services/content_server_config_service.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('ContentServerConfigService Unit Tests', () {
    setUp(() {
      SharedPreferences.setMockInitialValues({});
    });

    test('Default server URL is Emulator address', () {
      final service = ContentServerConfigService.instance;
      expect(service.currentServerUrl, ContentServerConfigService.defaultEmulatorUrl);
    });

    test('Set and sanitize server URL', () async {
      final service = ContentServerConfigService.instance;
      await service.setServerUrl('192.168.1.100:8000/');
      expect(service.currentServerUrl, 'http://192.168.1.100:8000');

      await service.setServerUrl('https://kids.mydomain.com/');
      expect(service.currentServerUrl, 'https://kids.mydomain.com');
    });
  });
}
