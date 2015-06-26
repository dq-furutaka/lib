package com.unicorn.utilities;

import android.content.Context;
import android.content.SharedPreferences;
import android.content.SharedPreferences.Editor;

/**
 * 
 * @author c1718
 *
 */
public class Preferences {

	private SharedPreferences pref;

	/**
	 * 
	 * @param con
	 */
	public Preferences(Context con) {
		this.pref = con.getSharedPreferences("Project_Pref", Context.MODE_PRIVATE);
	}

	/**
	 * 保存しているすべての値を削除する
	 */
	public void clear() {
		Editor editor = pref.edit();
		editor.clear();
		editor.commit();
	}

	/**
	 * 値を取得する
	 * 
	 * @param key キー
	 * @return デフォルトは0
	 */
	public int getIntValue(String key) {
		return pref.getInt(key, 0);
	}

	/**
	 * 値を設定する
	 * 
	 * @param key キー
	 * @param value 値
	 */
	public void setIntValue(String key, int value) {
		Editor editor = pref.edit();
		editor.putInt(key, value);
		editor.commit();
	}

	/**
	 * 値を取得する
	 * 
	 * @param key キー
	 * @return デフォルトは空文字
	 */
	public String getStringValue(String key) {
		return pref.getString(key, "");
	}

	/**
	 * 値を設定する
	 * 
	 * @param key キー
	 * @param value 値
	 */
	public void setStringValue(String key, String value) {
		Editor editor = pref.edit();
		editor.putString(key, value);
		editor.commit();
	}
	
	/**
	 * 値を取得する
	 * 
	 * @param key キー
	 * @return デフォルトは空文字
	 */
	public boolean getBooleanValue(String key) {
		return pref.getBoolean(key, true);
	}
	
	/**
	 * 値を設定する
	 * 
	 * @param key キー
	 * @param value 値
	 */
	public void setBooleanValue(String key, boolean value) {
		Editor editor = pref.edit();
		editor.putBoolean(key, value);
		editor.commit();
	}
}
