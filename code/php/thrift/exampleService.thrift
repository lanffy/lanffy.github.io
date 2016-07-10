namespace java example
namespace php example

/**
 * 异常代码
 */
enum ErrorCode {
    /**
     * 成功
     */
    SUCCESS = 0,

    /**
     * 失败
     */
    FAILED = -1,
}

/**
 * 异常代码含义
 */
const map<i16,string> ERROR_CODE_MESSAGE = {
    ErrorCode.SUCCESS: 'success',
    ErrorCode.FAILED: 'failed',
}

/**
 * 返回数据结构
 */
struct Data {
    /**
     * 加数
     */
    1: i32 data_one,

    /**
     * 被加数
     */
    2: i32 data_two,

    /**
     * 和
     */
    3: i32 sum,
}

/**
 * 服务返回结果
 */
struct Result {
    1: i32 code;
    2: string message;
    3: optional Data data;
}

/**
 * 服务返回结果
 */
service CalcService {
    /**
     * 计算两个数的和
     */
    Result sum(1:i32 data_one, 2:i32 data_two),
}

