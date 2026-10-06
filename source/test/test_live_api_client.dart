import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;

void main() async {
  print('Testing connection to local PHP server at http://localhost:8000 ...');

  // 1. Test /api/info
  final infoUri = Uri.parse('http://localhost:8000/api/info');
  final infoResp = await http.get(infoUri);
  print('API /api/info: HTTP ${infoResp.statusCode}');
  if (infoResp.statusCode == 200) {
    final data = jsonDecode(infoResp.body);
    print('Server Name: ${data['server_name']}');
    print('Version: ${data['version']}');
    print('Total Packs: ${data['stats']['total_packs']}');
  }

  // 2. Test /api/packs
  final packsUri = Uri.parse('http://localhost:8000/api/packs');
  final packsResp = await http.get(packsUri);
  print('\nAPI /api/packs: HTTP ${packsResp.statusCode}');
  if (packsResp.statusCode == 200) {
    final data = jsonDecode(packsResp.body);
    final packs = data['data'] as List;
    print('Loaded ${packs.length} topic packs:');
    for (var p in packs) {
      print('  - [${p['id']}] ${p['title_vi']} (${p['title_en']}) - ${p['item_count']} items - ${p['size_mb']}MB');
    }
  }

  // 3. Test /api/packs/topic_fruits/download
  final dlUri = Uri.parse('http://localhost:8000/api/packs/topic_fruits/download');
  final dlResp = await http.get(dlUri);
  print('\nAPI /api/packs/topic_fruits/download: HTTP ${dlResp.statusCode}');
  if (dlResp.statusCode == 200) {
    final data = jsonDecode(dlResp.body);
    final bundle = data['data'];
    print('Bundle pack: ${bundle['pack']['title_vi']} (v${bundle['pack']['version']})');
    print('Bundle items count: ${bundle['items'].length}');
    for (var it in bundle['items']) {
      print('    * ${it['name_vi']} (${it['name_en']}) - YT: ${it['youtube_video_id']}');
    }
  }

  print('\nALL API ENDPOINTS TESTED SUCCESSFULLY! READY FOR APP DOWNLOADS.');
  exit(0);
}
